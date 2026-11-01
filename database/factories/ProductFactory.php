<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(3, true));

        return [
            'product_name' => $name,
            'product_slug' => Str::slug($name),
            'description' => fake()->paragraphs(2, true),
            'price' => fake()->randomFloat(2, 5, 950),
            'stock' => fake()->numberBetween(0, 120),
            'product_thumbnail' => null,
            'is_active' => fake()->boolean(85),
        ];
    }

    /**
     * Indicate that the product is visible in the storefront.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the product is hidden from the storefront.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product is sold out.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stock' => 0,
        ]);
    }
}
