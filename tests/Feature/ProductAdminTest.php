<?php

namespace Tests\Feature;

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin/products')->assertRedirect('/admin/login');
    }

    public function test_an_admin_can_browse_the_products_table(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['product_name' => 'Nimbus Speaker']);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertOk()
            ->assertSee('Nimbus Speaker');
    }

    public function test_an_admin_can_open_the_dashboard_and_create_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Total products');

        $this->get('/admin/products/create')
            ->assertOk()
            ->assertSee('Product details');
    }

    public function test_an_admin_can_manage_brands_categories_and_tags(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin/brands')->assertOk()->assertSee('Brands');
        $this->get('/admin/brands/create')->assertOk()->assertSee('Brand details');

        $this->get('/admin/categories')->assertOk()->assertSee('Categories');
        $this->get('/admin/categories/create')->assertOk()->assertSee('Category details');

        $this->get('/admin/tags')->assertOk()->assertSee('Tags');
        $this->get('/admin/tags/create')->assertOk()->assertSee('Tag details');
    }

    public function test_an_admin_can_create_a_brand(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateBrand::class)
            ->fillForm([
                'brand_name' => 'Test Brand',
                'brand_slug' => 'test-brand',
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('brands', [
            'brand_name' => 'Test Brand',
            'brand_slug' => 'test-brand',
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_create_a_product(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'product_name' => 'Studio Headphones',
                'product_slug' => 'studio-headphones',
                'description' => 'Closed-back headphones with a balanced sound signature.',
                'price' => 199.99,
                'stock' => 25,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'product_name' => 'Studio Headphones',
            'product_slug' => 'studio-headphones',
            'stock' => 25,
            'is_active' => true,
        ]);
    }

    public function test_an_admin_can_update_and_delete_a_product(): void
    {
        $this->actingAs(User::factory()->create());

        $product = Product::factory()->create([
            'product_name' => 'Old Product Name',
            'product_slug' => 'old-product-name',
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'product_name' => 'New Product Name',
                'stock' => 3,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'New Product Name',
            'stock' => 3,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_an_admin_can_review_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $order = Order::factory()->create([
            'order_number' => 'PC-20261101-ABC123',
            'customer_name' => 'Test Buyer',
            'customer_email' => 'buyer@example.com',
            'total_amount' => 250,
            'currency' => 'USD',
        ]);

        $order->items()->create([
            'product_name' => 'Aurora Desk Lamp',
            'unit_price' => 125,
            'quantity' => 2,
            'line_total' => 250,
        ]);

        $this->get('/admin/orders')
            ->assertOk()
            ->assertSee('PC-20261101-ABC123')
            ->assertSee('Test Buyer');

        $this->get('/admin/orders/'.$order->order_number)
            ->assertOk()
            ->assertSee('PC-20261101-ABC123')
            ->assertSee('Aurora Desk Lamp');
    }
}
