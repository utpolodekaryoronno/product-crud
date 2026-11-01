<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFrontendTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_storefront_lists_only_active_products(): void
    {
        $visible = Product::factory()->active()->create(['product_name' => 'Visible Trail Pack']);
        Product::factory()->inactive()->create(['product_name' => 'Retired Canvas Tote']);

        $this->get('/')
            ->assertOk()
            ->assertSee($visible->product_name)
            ->assertDontSee('Retired Canvas Tote');
    }

    public function test_products_can_be_searched_by_name(): void
    {
        Product::factory()->active()->create(['product_name' => 'Alpine Daypack']);
        Product::factory()->active()->create(['product_name' => 'Ceramic Pour Over']);

        $this->get('/?search=Daypack')
            ->assertOk()
            ->assertSee('Alpine Daypack')
            ->assertDontSee('Ceramic Pour Over');
    }

    public function test_products_can_be_sorted_by_price(): void
    {
        $cheaper = Product::factory()->active()->create(['price' => 10]);
        $pricier = Product::factory()->active()->create(['price' => 900]);

        $this->get('/?sort=price_asc')
            ->assertOk()
            ->assertSeeInOrder([$cheaper->product_name, $pricier->product_name]);
    }

    public function test_a_product_detail_page_can_be_viewed(): void
    {
        $product = Product::factory()->active()->create([
            'product_name' => 'Aurora Desk Lamp',
            'description' => 'A warm and dimmable lamp for late-night reading.',
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Aurora Desk Lamp')
            ->assertSee('A warm and dimmable lamp for late-night reading.');
    }

    public function test_hidden_products_return_a_not_found_response(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_a_unique_slug_is_generated_when_one_is_not_provided(): void
    {
        $first = Product::create([
            'product_name' => 'Studio Headphones',
            'price' => 100,
            'stock' => 10,
            'is_active' => true,
        ]);

        $second = Product::create([
            'product_name' => 'Studio Headphones',
            'price' => 120,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->assertSame('studio-headphones', $first->product_slug);
        $this->assertSame('studio-headphones-2', $second->product_slug);
    }
}
