<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'PC-'.now()->format('Ymd').'-'.fake()->unique()->bothify('???###'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('01#########'),
            'shipping_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postcode' => fake()->postcode(),
            'notes' => null,
            'total_amount' => fake()->randomFloat(2, 10, 500),
            'currency' => config('shop.currency', 'USD'),
            'status' => OrderStatus::Pending,
            'payment_gateway' => 'sslcommerz',
            'tran_id' => 'TXN-'.fake()->unique()->bothify('????????????????'),
            'val_id' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Indicate that the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
