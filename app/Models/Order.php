<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'order_number',
    'customer_name',
    'customer_email',
    'customer_phone',
    'shipping_address',
    'city',
    'postcode',
    'notes',
    'total_amount',
    'currency',
    'status',
    'payment_gateway',
    'tran_id',
    'val_id',
    'paid_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'status' => OrderStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Resolve orders in public URLs using the human friendly order number.
     */
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Determine whether the order has been paid in full.
     */
    public function isPaid(): bool
    {
        return $this->status === OrderStatus::Paid;
    }

    /**
     * Build an order number that is unique across all orders.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'PC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (static::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Build a transaction id that is unique across all orders.
     */
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN-'.Str::upper(Str::random(16));
        } while (static::query()->where('tran_id', $transactionId)->exists());

        return $transactionId;
    }
}
