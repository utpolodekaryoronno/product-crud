<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_product_can_be_added_to_the_cart(): void
    {
        $product = Product::factory()->active()->create([
            'product_name' => 'Aurora Desk Lamp',
            'stock' => 10,
        ]);

        $this->from(route('products.show', $product))
            ->post(route('cart.store'), ['product_id' => $product->id])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status');

        $this->assertSame([$product->id => 1], session('cart.items'));

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Aurora Desk Lamp')
            ->assertSee('1 item');
    }

    public function test_adding_the_same_product_again_increases_its_quantity(): void
    {
        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 3]);

        $this->assertSame([$product->id => 5], session('cart.items'));
    }

    public function test_the_cart_quantity_cannot_exceed_the_available_stock(): void
    {
        $product = Product::factory()->active()->create(['stock' => 3]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'quantity' => 9]);

        $this->assertSame([$product->id => 3], session('cart.items'));
    }

    public function test_cart_items_can_be_updated_and_removed(): void
    {
        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]]);

        $this->patch(route('cart.update', $product), ['quantity' => 4])
            ->assertSessionHas('status');

        $this->assertSame([$product->id => 4], session('cart.items'));

        $this->delete(route('cart.destroy', $product))
            ->assertSessionHas('status');

        $this->assertSame([], session('cart.items', []));
    }

    public function test_out_of_stock_products_cannot_be_added_to_the_cart(): void
    {
        $product = Product::factory()->active()->create(['stock' => 0]);

        $this->post(route('cart.store'), ['product_id' => $product->id])
            ->assertSessionHas('error');

        $this->assertNull(session('cart.items'));
    }

    public function test_hidden_products_cannot_be_added_to_the_cart(): void
    {
        $product = Product::factory()->inactive()->create(['stock' => 5]);

        $this->post(route('cart.store'), ['product_id' => $product->id])
            ->assertSessionHas('error');

        $this->assertNull(session('cart.items'));
    }

    public function test_the_cart_can_be_cleared(): void
    {
        $product = Product::factory()->active()->create(['stock' => 5]);

        $this->withSession(['cart.items' => [$product->id => 2]]);

        $this->delete(route('cart.clear'))
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status');

        $this->assertSame([], session('cart.items', []));
    }
}
