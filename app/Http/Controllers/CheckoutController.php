<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\PayPalException;
use App\Exceptions\SslCommerzException;
use App\Models\Order;
use App\Services\CartService;
use App\Services\PayPalService;
use App\Services\SslCommerzService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckoutController extends Controller
{
    /**
     * Show the checkout form.
     */
    public function create(CartService $cart, SslCommerzService $gateway, PayPalService $paypal): View|RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty. Add a product before checking out.');
        }

        return view('checkout.create', [
            'items' => $items,
            'subtotal' => $cart->subtotal(),
            'gatewayConfigured' => $gateway->isConfigured(),
            'testMode' => $gateway->testModeEnabled(),
            'paypalConfigured' => $paypal->isConfigured(),
            'paypalCurrencySupported' => $paypal->supportsCurrency((string) config('shop.currency')),
            'currency' => config('shop.currency'),
        ]);
    }

    /**
     * Create the order and redirect the customer to the selected gateway.
     */
    public function store(Request $request, CartService $cart, SslCommerzService $gateway, PayPalService $paypal): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['nullable', 'in:sslcommerz,paypal'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postcode' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty. Add a product before checking out.');
        }

        $paymentMethod = $validated['payment_method'] ?? 'sslcommerz';
        unset($validated['payment_method']);

        $testMode = $gateway->testModeEnabled();

        if ($paymentMethod === 'paypal') {
            if (! $paypal->supportsCurrency((string) config('shop.currency'))) {
                return back()->withInput()->with('error', 'PayPal does not support this store currency. Please choose SSLCommerz.');
            }

            if (! $paypal->isConfigured()) {
                return back()->withInput()->with('error', 'PayPal is not configured yet. Please choose another payment method.');
            }
        } elseif (! $testMode && ! $gateway->isConfigured()) {
            return back()
                ->withInput()
                ->with('error', 'SSLCommerz is not configured yet. Add your store credentials to the environment file.');
        }

        $order = DB::transaction(function () use ($items, $validated, $paymentMethod): Order {
            $order = Order::create([
                ...$validated,
                'order_number' => Order::generateOrderNumber(),
                'tran_id' => Order::generateTransactionId(),
                'total_amount' => round((float) $items->sum('line_total'), 2),
                'currency' => $paymentMethod === 'paypal'
                    ? config('shop.currency')
                    : config('services.sslcommerz.currency'),
                'status' => OrderStatus::Pending,
                'payment_gateway' => $paymentMethod,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->product_name,
                    'unit_price' => $item['product']->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
            }

            return $order;
        });

        $request->session()->put('checkout.order_id', $order->id);

        if ($paymentMethod === 'paypal') {
            try {
                $payment = $paypal->createOrder($order);
                $order->update(['paypal_order_id' => $payment['order_id']]);

                return redirect()->away($payment['approval_url']);
            } catch (PayPalException $exception) {
                report($exception);
                $order->update(['status' => OrderStatus::Failed]);

                return back()->withInput()->with('error', 'We could not start PayPal checkout. Please try again later.');
            }
        }

        if ($testMode) {
            return redirect()->route('payments.sslcommerz.simulate', $order);
        }

        try {
            $gatewayUrl = $gateway->initiate($order);
        } catch (SslCommerzException $exception) {
            report($exception);

            $order->update(['status' => OrderStatus::Failed]);

            return back()
                ->withInput()
                ->with('error', 'We could not start the payment. Please try again in a moment.');
        } catch (Throwable $exception) {
            report($exception);

            $order->update(['status' => OrderStatus::Failed]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong while starting the payment. Please try again.');
        }

        return redirect()->away($gatewayUrl);
    }

    /**
     * Show the confirmation page for a completed order.
     */
    public function confirmation(Request $request, Order $order): View
    {
        abort_unless((int) $request->session()->get('checkout.order_id') === $order->id, 404);

        $order->load('items.product');

        return view('checkout.confirmation', [
            'order' => $order,
        ]);
    }
}
