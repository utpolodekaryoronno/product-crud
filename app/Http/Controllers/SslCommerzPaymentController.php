<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\SslCommerzException;
use App\Models\Order;
use App\Services\CartService;
use App\Services\PaidOrderService;
use App\Services\SslCommerzService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SslCommerzPaymentController extends Controller
{
    public function __construct(private readonly PaidOrderService $paidOrders) {}

    /**
     * Handle the browser redirect after a successful payment.
     */
    public function success(Request $request, SslCommerzService $gateway, CartService $cart): RedirectResponse
    {
        $order = $this->findOrder($request);

        if (! $order) {
            return redirect()
                ->route('products.index')
                ->with('error', 'We could not find that order.');
        }

        if ($order->isPaid()) {
            $this->clearCheckoutCart($request, $order, $cart);

            return $this->confirmationRedirect($request, $order);
        }

        $validationId = $request->string('val_id')->toString();

        if ($validationId === '') {
            return redirect()
                ->route('checkout.create')
                ->with('error', 'The payment was not completed. Please try again.');
        }

        try {
            $validation = $gateway->validate($validationId);
        } catch (SslCommerzException $exception) {
            report($exception);

            return redirect()
                ->route('checkout.create')
                ->with('error', 'We could not verify the payment. Please contact support before trying again.');
        }

        if (! $gateway->paymentMatchesOrder($validation, $order)) {
            return redirect()
                ->route('checkout.create')
                ->with('error', 'The payment details did not match this order.');
        }

        $this->paidOrders->markAsPaid($order, $validationId);

        $this->clearCheckoutCart($request, $order, $cart);

        return $this->confirmationRedirect($request, $order);
    }

    /**
     * Handle the browser redirect after a failed payment.
     */
    public function fail(): RedirectResponse
    {
        return redirect()
            ->route('checkout.create')
            ->with('error', 'The payment was not completed. You can try again.');
    }

    /**
     * Handle the browser redirect after the customer cancels the payment.
     */
    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('cart.index')
            ->with('error', 'The payment was cancelled. Your cart is still waiting for you.');
    }

    /**
     * Handle the server-to-server instant payment notification.
     */
    public function ipn(Request $request, SslCommerzService $gateway): JsonResponse
    {
        $order = $this->findOrder($request);
        $validationId = $request->string('val_id')->toString();

        if (! $order || $validationId === '') {
            return response()->json(['status' => 'ignored'], 422);
        }

        if ($order->isPaid()) {
            return response()->json(['status' => 'already-paid']);
        }

        try {
            $validation = $gateway->validate($validationId);
        } catch (SslCommerzException $exception) {
            report($exception);

            return response()->json(['status' => 'validation-failed'], 502);
        }

        if (! $gateway->paymentMatchesOrder($validation, $order)) {
            return response()->json(['status' => 'mismatch'], 422);
        }

        $this->paidOrders->markAsPaid($order, $validationId);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Show the simulated payment page used while test mode is enabled.
     */
    public function simulate(Request $request, Order $order, SslCommerzService $gateway): View|RedirectResponse
    {
        $this->authorizeSimulatedOrder($request, $order, $gateway);

        if ($order->isPaid()) {
            return $this->confirmationRedirect($request, $order);
        }

        $order->load('items');

        return view('checkout.simulate', [
            'order' => $order,
        ]);
    }

    /**
     * Complete a simulated payment successfully.
     */
    public function simulateSuccess(Request $request, Order $order, CartService $cart, SslCommerzService $gateway): RedirectResponse
    {
        $this->authorizeSimulatedOrder($request, $order, $gateway);

        if (! $order->isPaid()) {
            $this->paidOrders->markAsPaid($order, 'TEST-'.Str::upper(Str::random(12)));
        }

        $this->clearCheckoutCart($request, $order, $cart);

        return $this->confirmationRedirect($request, $order);
    }

    /**
     * Decline a simulated payment.
     */
    public function simulateFail(Request $request, Order $order, SslCommerzService $gateway): RedirectResponse
    {
        $this->authorizeSimulatedOrder($request, $order, $gateway);

        if (! $order->isPaid()) {
            $order->update(['status' => OrderStatus::Failed]);
        }

        return redirect()
            ->route('checkout.create')
            ->with('error', 'Test payment declined. Your cart is still waiting for you.');
    }

    /**
     * Cancel a simulated payment.
     */
    public function simulateCancel(Request $request, Order $order, SslCommerzService $gateway): RedirectResponse
    {
        $this->authorizeSimulatedOrder($request, $order, $gateway);

        if (! $order->isPaid()) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }

        return redirect()
            ->route('cart.index')
            ->with('error', 'Test payment cancelled. Your cart is still waiting for you.');
    }

    /**
     * Ensure simulated payments are enabled and belong to the current session.
     */
    private function authorizeSimulatedOrder(Request $request, Order $order, SslCommerzService $gateway): void
    {
        abort_unless($gateway->testModeEnabled(), 404);
        abort_unless((int) $request->session()->get('checkout.order_id') === $order->id, 404);
    }

    /**
     * Find the order referenced by the SSLCommerz transaction id.
     */
    private function findOrder(Request $request): ?Order
    {
        $transactionId = $request->string('tran_id')->toString();

        if ($transactionId === '') {
            return null;
        }

        return Order::query()
            ->where('tran_id', $transactionId)
            ->first();
    }

    /**
     * Clear only the cart associated with the current checkout session.
     */
    private function clearCheckoutCart(Request $request, Order $order, CartService $cart): void
    {
        if ((int) $request->session()->get('checkout.order_id') === $order->id) {
            $cart->clear();
        }
    }

    /**
     * Redirect to the confirmation page without granting access to other sessions.
     */
    private function confirmationRedirect(Request $request, Order $order): RedirectResponse
    {
        if ((int) $request->session()->get('checkout.order_id') !== $order->id) {
            return redirect()
                ->route('products.index')
                ->with('status', 'Payment received. An order confirmation will be sent to the customer email address.');
        }

        return redirect()->route('checkout.confirmation', $order);
    }
}
