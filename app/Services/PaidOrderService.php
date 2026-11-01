<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaidOrderService
{
    /**
     * Only the pending-to-paid transition triggers stock reduction and email.
     */
    public function markAsPaid(Order $order, string $validationId): void
    {
        $transitioned = DB::transaction(function () use ($order, $validationId): bool {
            $updated = Order::query()
                ->whereKey($order->id)
                ->where('status', OrderStatus::Pending->value)
                ->update([
                    'status' => OrderStatus::Paid->value,
                    'val_id' => $validationId,
                    'paid_at' => now(),
                ]);

            if ($updated === 0) {
                return false;
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

            return true;
        });

        if (! $transitioned) {
            return;
        }

        $order->refresh()->load('items');

        try {
            Mail::to($order->customer_email)->send(new OrderConfirmation($order));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
