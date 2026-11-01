<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
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

    public function test_products_can_be_filtered_by_brand_category_and_tag(): void
    {
        $brand = Brand::factory()->create(['brand_name' => 'Alpine Works', 'brand_slug' => 'alpine-works']);
        $category = Category::factory()->create(['category_name' => 'Bags and Travel', 'category_slug' => 'bags-and-travel']);
        $tag = Tag::factory()->create(['tag_name' => 'Bestseller', 'tag_slug' => 'bestseller']);

        $matching = Product::factory()->active()->create([
            'product_name' => 'Alpine Daypack',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        $matching->tags()->attach($tag);

        Product::factory()->active()->create(['product_name' => 'Ceramic Pour Over']);

        $this->get('/?brand=alpine-works')
            ->assertOk()
            ->assertSee('Alpine Daypack')
            ->assertDontSee('Ceramic Pour Over');

        $this->get('/?category=bags-and-travel')
            ->assertOk()
            ->assertSee('Alpine Daypack')
            ->assertDontSee('Ceramic Pour Over');

        $this->get('/?tag=bestseller')
            ->assertOk()
            ->assertSee('Alpine Daypack')
            ->assertDontSee('Ceramic Pour Over');
    }

    public function test_a_product_detail_page_shows_its_brand_category_and_tags(): void
    {
        $brand = Brand::factory()->create(['brand_name' => 'Alpine Works', 'brand_slug' => 'alpine-works']);
        $category = Category::factory()->create(['category_name' => 'Bags and Travel', 'category_slug' => 'bags-and-travel']);
        $tag = Tag::factory()->create(['tag_name' => 'Bestseller', 'tag_slug' => 'bestseller']);

        $product = Product::factory()->active()->create([
            'product_name' => 'Alpine Daypack',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        $product->tags()->attach($tag);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Alpine Works')
            ->assertSee('Bags and Travel')
            ->assertSee('Bestseller');
    }

    public function test_the_product_image_supports_hover_zoom(): void
    {
        $product = Product::factory()->active()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-zoom-container', false)
            ->assertSee('data-zoom-image', false)
            ->assertSee('Hover to zoom');
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
