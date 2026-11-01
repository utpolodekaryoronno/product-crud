<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\PayPalException;
use App\Models\Order;
use App\Services\CartService;
use App\Services\PaidOrderService;
use App\Services\PayPalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayPalPaymentController extends Controller
{
    public function __construct(private readonly PaidOrderService $paidOrders) {}

    /**
     * Capture the PayPal order server-side after the payer approves it.
     */
    public function success(Request $request, PayPalService $paypal, CartService $cart): RedirectResponse
    {
        $paypalOrderId = $request->query('token');

        if (! is_string($paypalOrderId) || $paypalOrderId === '') {
            return redirect()->route('cart.index')->with('error', 'PayPal did not provide an order reference.');
        }

        $order = Order::query()
            ->where('payment_gateway', 'paypal')
            ->where('paypal_order_id', $paypalOrderId)
            ->first();

        if (! $order || (int) $request->session()->get('checkout.order_id') !== $order->id) {
            return redirect()->route('cart.index')->with('error', 'We could not verify this PayPal checkout session.');
        }

        if ($order->isPaid()) {
            $cart->clear();

            return redirect()->route('checkout.confirmation', $order);
        }

        if ($order->status !== OrderStatus::Pending) {
            return redirect()->route('cart.index')->with('error', 'This order is no longer awaiting payment.');
        }

        try {
            $capture = $paypal->capture($order);
        } catch (PayPalException $exception) {
            report($exception);

            return redirect()->route('cart.index')
                ->with('error', 'We could not verify the PayPal payment. Please contact support before trying again.');
        }

        if (! $paypal->captureMatchesOrder($capture, $order)) {
            return redirect()->route('cart.index')
                ->with('error', 'PayPal did not confirm the expected payment. Please contact support.');
        }

        $captureId = $capture['purchase_units'][0]['payments']['captures'][0]['id'];
        $this->paidOrders->markAsPaid($order, $captureId);
        $cart->clear();

        return redirect()->route('checkout.confirmation', $order);
    }

    /**
     * A browser cancellation is not proof that PayPal cancelled the payment.
     */
    public function cancel(): RedirectResponse
    {
        return redirect()->route('cart.index')
            ->with('error', 'PayPal checkout was cancelled. Your cart is still waiting for you.');
    }
}
