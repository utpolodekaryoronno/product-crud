<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Exceptions\SslCommerzException;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\SslCommerzService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SslCommerzPaymentController extends Controller
{
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
            return $this->confirmationRedirect($request, $order);
        }

        $validationId = $request->string('val_id')->toString();

        if ($validationId === '') {
            $order->update(['status' => OrderStatus::Failed]);

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
            $order->update([
                'status' => OrderStatus::Failed,
                'val_id' => $validationId,
            ]);

            return redirect()
                ->route('checkout.create')
                ->with('error', 'The payment details did not match this order.');
        }

        $this->markAsPaid($order, $validationId);

        $cart->clear();

        return $this->confirmationRedirect($request, $order);
    }

    /**
     * Handle the browser redirect after a failed payment.
     */
    public function fail(Request $request): RedirectResponse
    {
        $order = $this->findOrder($request);

        if ($order && ! $order->isPaid()) {
            $order->update(['status' => OrderStatus::Failed]);
        }

        return redirect()
            ->route('checkout.create')
            ->with('error', 'The payment was not completed. You can try again.');
    }

    /**
     * Handle the browser redirect after the customer cancels the payment.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $order = $this->findOrder($request);

        if ($order && ! $order->isPaid()) {
            $order->update(['status' => OrderStatus::Cancelled]);
        }

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

        $this->markAsPaid($order, $validationId);

        return response()->json(['status' => 'ok']);
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
     * Mark the order as paid and reduce product stock exactly once.
     */
    private function markAsPaid(Order $order, string $validationId): void
    {
        DB::transaction(function () use ($order, $validationId): void {
            $updated = Order::query()
                ->whereKey($order->id)
                ->where('status', OrderStatus::Pending->value)
                ->update([
                    'status' => OrderStatus::Paid->value,
                    'val_id' => $validationId,
                    'paid_at' => now(),
                ]);

            if ($updated === 0) {
                return;
            }

            $order->loadMissing('items');

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                Product::query()
                    ->whereKey($item->product_id)
                    ->where('stock', '>=', $item->quantity)
                    ->decrement('stock', $item->quantity);
            }
        });
    }

    /**
     * Remember the order and send the customer to its confirmation page.
     */
    private function confirmationRedirect(Request $request, Order $order): RedirectResponse
    {
        $request->session()->put('checkout.order_id', $order->id);

        return redirect()->route('checkout.confirmation', $order);
    }
}
