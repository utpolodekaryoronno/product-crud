<?php

namespace App\Services;

use App\Exceptions\PayPalException;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PayPalService
{
    private const SUPPORTED_CURRENCIES = [
        'AUD', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD',
        'ILS', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'SEK', 'SGD',
        'THB', 'USD',
    ];

    public function isConfigured(): bool
    {
        return filled(config('services.paypal.client_id'))
            && filled(config('services.paypal.client_secret'));
    }

    public function supportsCurrency(string $currency): bool
    {
        return in_array(strtoupper($currency), self::SUPPORTED_CURRENCIES, true);
    }

    /**
     * @return array{order_id: string, approval_url: string}
     */
    public function createOrder(Order $order): array
    {
        $this->ensureAvailable($order);
        $response = $this->request('POST', '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->order_number,
                'custom_id' => $order->tran_id,
                'amount' => [
                    'currency_code' => $order->currency,
                    'value' => number_format((float) $order->total_amount, 2, '.', ''),
                ],
            ]],
            'application_context' => [
                'return_url' => route('payments.paypal.return'),
                'cancel_url' => route('payments.paypal.cancel'),
                'user_action' => 'PAY_NOW',
            ],
        ], $order->tran_id.'-create');

        $orderId = $response['id'] ?? null;
        $links = $response['links'] ?? [];
        $approvalLink = is_array($links)
            ? collect($links)->first(fn (mixed $link): bool => is_array($link) && in_array($link['rel'] ?? null, ['payer-action', 'approve'], true))
            : null;
        $approvalUrl = is_array($approvalLink) ? ($approvalLink['href'] ?? null) : null;

        if (! in_array($response['status'] ?? null, ['PAYER_ACTION_REQUIRED', 'CREATED'], true)
            || ! is_string($orderId) || $orderId === ''
            || ! is_string($approvalUrl) || ! $this->isPayPalApprovalUrl($approvalUrl)) {
            throw new PayPalException('PayPal did not provide a valid approval link.');
        }

        return ['order_id' => $orderId, 'approval_url' => $approvalUrl];
    }

    /**
     * @return array<string, mixed>
     */
    public function capture(Order $order): array
    {
        $this->ensureAvailable($order);

        if (! is_string($order->paypal_order_id) || $order->paypal_order_id === '') {
            throw new PayPalException('The PayPal order has not been created.');
        }

        return $this->request(
            'POST',
            '/v2/checkout/orders/'.rawurlencode($order->paypal_order_id).'/capture',
            [],
            $order->tran_id.'-capture',
        );
    }

    /**
     * Confirm that PayPal captured the exact local order, not merely that the browser returned.
     *
     * @param  array<string, mixed>  $response
     */
    public function captureMatchesOrder(array $response, Order $order): bool
    {
        if (($response['id'] ?? null) !== $order->paypal_order_id
            || ($response['status'] ?? null) !== 'COMPLETED') {
            return false;
        }

        $units = $response['purchase_units'] ?? null;

        if (! is_array($units) || count($units) !== 1 || ! is_array($units[0])) {
            return false;
        }

        $unit = $units[0];
        $captures = $unit['payments']['captures'] ?? null;

        if (($unit['reference_id'] ?? null) !== $order->order_number
            || ($unit['custom_id'] ?? null) !== $order->tran_id
            || ! is_array($unit['amount'] ?? null)
            || ($unit['amount']['currency_code'] ?? null) !== $order->currency
            || ($unit['amount']['value'] ?? null) !== number_format((float) $order->total_amount, 2, '.', '')
            || ! is_array($captures) || count($captures) !== 1 || ! is_array($captures[0])) {
            return false;
        }

        $capture = $captures[0];
        $amount = $capture['amount'] ?? null;

        return ($capture['status'] ?? null) === 'COMPLETED'
            && is_string($capture['id'] ?? null)
            && $capture['id'] !== ''
            && is_array($amount)
            && ($amount['currency_code'] ?? null) === $order->currency
            && ($amount['value'] ?? null) === number_format((float) $order->total_amount, 2, '.', '')
            && ($capture['final_capture'] ?? null) === true;
    }

    private function isPayPalApprovalUrl(string $url): bool
    {
        $parts = parse_url($url);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && ($parts['host'] ?? null) === (config('services.paypal.sandbox') ? 'www.sandbox.paypal.com' : 'www.paypal.com')
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['port']);
    }

    private function ensureAvailable(Order $order): void
    {
        if (! $this->isConfigured() || ! $this->supportsCurrency($order->currency)) {
            throw new PayPalException('PayPal is not available for this order.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload, string $requestId): array
    {
        $token = $this->accessToken();

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->withHeaders(['PayPal-Request-Id' => $requestId])
                ->connectTimeout(5)
                ->timeout(20)
                ->send($method, $this->baseUrl().$path, ['json' => $payload]);
        } catch (ConnectionException $exception) {
            throw new PayPalException('Could not connect to PayPal.', 0, $exception);
        }

        $data = $response->json();

        if (! $response->successful() || ! is_array($data)) {
            throw new PayPalException('PayPal could not process the payment request.');
        }

        return $data;
    }

    private function accessToken(): string
    {
        try {
            $response = Http::withBasicAuth(
                (string) config('services.paypal.client_id'),
                (string) config('services.paypal.client_secret'),
            )->asForm()->acceptJson()->connectTimeout(5)->timeout(20)
                ->post($this->baseUrl().'/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        } catch (ConnectionException $exception) {
            throw new PayPalException('Could not authenticate with PayPal.', 0, $exception);
        }

        $token = $response->json('access_token');

        if (! $response->successful() || ! is_string($token) || $token === '') {
            throw new PayPalException('PayPal authentication failed.');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        return config('services.paypal.sandbox')
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }
}
