<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

class CartService
{
    /**
     * The session key that stores product ids mapped to quantities.
     */
    private const SESSION_KEY = 'cart.items';

    public function __construct(private readonly Session $session) {}

    /**
     * Get the raw cart contents keyed by product id.
     *
     * @return array<int, int>
     */
    public function contents(): array
    {
        $contents = $this->session->get(self::SESSION_KEY, []);

        if (! is_array($contents)) {
            return [];
        }

        $normalised = [];

        foreach ($contents as $productId => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity > 0) {
                $normalised[(int) $productId] = $quantity;
            }
        }

        return $normalised;
    }

    /**
     * Get the cart items with their related products.
     *
     * @return Collection<int, array{product: Product, quantity: int, line_total: float}>
     */
    public function items(): Collection
    {
        $contents = $this->contents();

        if ($contents === []) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', array_keys($contents))
            ->active()
            ->where('stock', '>', 0)
            ->get()
            ->keyBy('id');

        $missing = array_diff(array_keys($contents), $products->keys()->all());

        if ($missing !== []) {
            $this->forget($missing);
        }

        return collect($contents)
            ->map(function (int $quantity, int $productId) use ($products): ?array {
                $product = $products->get($productId);

                if (! $product) {
                    return null;
                }

                $quantity = max(1, min($quantity, $product->stock));

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total' => round((float) $product->price * $quantity, 2),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Count the total number of units in the cart.
     */
    public function count(): int
    {
        return array_sum($this->contents());
    }

    /**
     * Calculate the cart subtotal.
     */
    public function subtotal(): float
    {
        return round((float) $this->items()->sum('line_total'), 2);
    }

    /**
     * Determine whether the cart has any items.
     */
    public function isEmpty(): bool
    {
        return $this->items()->isEmpty();
    }

    /**
     * Add a product to the cart, never exceeding the available stock.
     */
    public function add(Product $product, int $quantity = 1): void
    {
        if ($product->is_out_of_stock) {
            return;
        }

        $contents = $this->contents();
        $currentQuantity = $contents[$product->id] ?? 0;

        $contents[$product->id] = max(1, min($currentQuantity + $quantity, $product->stock));

        $this->session->put(self::SESSION_KEY, $contents);
    }

    /**
     * Set the quantity for a product, removing it when the quantity is zero.
     */
    public function update(Product $product, int $quantity): void
    {
        $contents = $this->contents();

        if ($quantity <= 0 || ! $product->is_active || $product->is_out_of_stock) {
            unset($contents[$product->id]);
        } else {
            $contents[$product->id] = min($quantity, $product->stock);
        }

        $this->session->put(self::SESSION_KEY, $contents);
    }

    /**
     * Remove a product from the cart.
     */
    public function remove(Product $product): void
    {
        $this->forget([$product->id]);
    }

    /**
     * Empty the cart.
     */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Remove the given product ids from the cart.
     *
     * @param  array<int, int|string>  $productIds
     */
    private function forget(array $productIds): void
    {
        $contents = $this->contents();

        foreach ($productIds as $productId) {
            unset($contents[(int) $productId]);
        }

        if ($contents === []) {
            $this->session->forget(self::SESSION_KEY);

            return;
        }

        $this->session->put(self::SESSION_KEY, $contents);
    }
}
