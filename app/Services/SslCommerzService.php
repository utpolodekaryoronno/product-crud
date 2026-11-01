<?php

namespace App\Services;

use App\Exceptions\SslCommerzException;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SslCommerzService
{
    /**
     * Determine whether the gateway credentials have been configured.
     */
    public function isConfigured(): bool
    {
        return filled(config('services.sslcommerz.store_id'))
            && filled(config('services.sslcommerz.store_password'));
    }

    /**
     * Create a payment session and return the hosted checkout URL.
     *
     * @throws SslCommerzException
     */
    public function initiate(Order $order): string
    {
        $this->ensureConfigured();

        try {
            $response = Http::asForm()
                ->connectTimeout(5)
                ->timeout(20)
                ->post($this->baseUrl().'/gwprocess/v4/api.php', $this->payload($order));
        } catch (ConnectionException $exception) {
            throw new SslCommerzException('Could not connect to SSLCommerz.', 0, $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new SslCommerzException('SSLCommerz returned an unexpected response.');
        }

        $gatewayUrl = $payload['GatewayPageURL'] ?? null;

        if (($payload['status'] ?? null) !== 'SUCCESS' || ! is_string($gatewayUrl) || ! str_starts_with($gatewayUrl, 'https://')) {
            throw new SslCommerzException((string) ($payload['failedreason'] ?? 'SSLCommerz rejected the payment request.'));
        }

        return $gatewayUrl;
    }

    /**
     * Validate a completed payment with SSLCommerz.
     *
     * @return array<string, mixed>
     *
     * @throws SslCommerzException
     */
    public function validate(string $validationId): array
    {
        $this->ensureConfigured();

        try {
            $response = Http::connectTimeout(5)
                ->timeout(20)
                ->get($this->baseUrl().'/validator/api/validationserverAPI.php', [
                    'val_id' => $validationId,
                    'store_id' => config('services.sslcommerz.store_id'),
                    'store_passwd' => config('services.sslcommerz.store_password'),
                    'format' => 'json',
                ]);
        } catch (ConnectionException $exception) {
            throw new SslCommerzException('Could not connect to SSLCommerz.', 0, $exception);
        }

        $payload = $response->json();

        if (! $response->successful() || ! is_array($payload)) {
            throw new SslCommerzException('SSLCommerz returned an unexpected validation response.');
        }

        return $payload;
    }

    /**
     * Determine whether a validation payload matches the given order.
     *
     * @param  array<string, mixed>  $validation
     */
    public function paymentMatchesOrder(array $validation, Order $order): bool
    {
        $status = $validation['status'] ?? null;

        if (! in_array($status, ['VALID', 'VALIDATED'], true)) {
            return false;
        }

        if (($validation['tran_id'] ?? null) !== $order->tran_id) {
            return false;
        }

        if (($validation['currency'] ?? null) !== $order->currency) {
            return false;
        }

        return abs((float) ($validation['amount'] ?? 0) - (float) $order->total_amount) < 0.01;
    }

    /**
     * Build the payload sent to the SSLCommerz payment session endpoint.
     *
     * @return array<string, mixed>
     */
    private function payload(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'store_id' => config('services.sslcommerz.store_id'),
            'store_passwd' => config('services.sslcommerz.store_password'),
            'total_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'currency' => $order->currency,
            'tran_id' => $order->tran_id,
            'success_url' => route('payments.sslcommerz.success'),
            'fail_url' => route('payments.sslcommerz.fail'),
            'cancel_url' => route('payments.sslcommerz.cancel'),
            'ipn_url' => route('payments.sslcommerz.ipn'),
            'cus_name' => $order->customer_name,
            'cus_email' => $order->customer_email,
            'cus_add1' => $order->shipping_address,
            'cus_city' => $order->city,
            'cus_postcode' => $order->postcode ?? '1000',
            'cus_country' => config('services.sslcommerz.country'),
            'cus_phone' => $order->customer_phone,
            'shipping_method' => 'NO',
            'num_of_item' => (int) $order->items->sum('quantity'),
            'product_name' => 'Order '.$order->order_number,
            'product_category' => 'General',
            'product_profile' => 'general',
            'product_amount' => number_format((float) $order->total_amount, 2, '.', ''),
            'vat' => '0.00',
            'discount' => '0.00',
            'convenience_fee' => '0.00',
        ];
    }

    /**
     * Get the base URL for the configured SSLCommerz environment.
     */
    private function baseUrl(): string
    {
        return config('services.sslcommerz.sandbox')
            ? 'https://sandbox.sslcommerz.com'
            : 'https://securepay.sslcommerz.com';
    }

    /**
     * Ensure the gateway is ready to be used.
     *
     * @throws SslCommerzException
     */
    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new SslCommerzException('SSLCommerz credentials are not configured.');
        }
    }
}
