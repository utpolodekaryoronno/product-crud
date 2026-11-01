<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.sslcommerz.store_id', 'test-store');
        config()->set('services.sslcommerz.store_password', 'test-password');
        config()->set('services.sslcommerz.sandbox', true);
        config()->set('services.sslcommerz.test_mode', false);
        config()->set('services.sslcommerz.currency', 'USD');
    }

    public function test_checkout_requires_products_in_the_cart(): void
    {
        $this->get(route('checkout.create'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_the_checkout_page_shows_the_order_summary(): void
    {
        $product = Product::factory()->active()->create([
            'product_name' => 'Alpine Daypack',
            'price' => 74,
            'stock' => 10,
        ]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('Alpine Daypack')
            ->assertSee('SSLCommerz');
    }

    public function test_checkout_creates_an_order_and_redirects_to_sslcommerz(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/gwprocess/*' => Http::response([
                'status' => 'SUCCESS',
                'GatewayPageURL' => 'https://sandbox.sslcommerz.com/EasyCheckOut/test-session',
            ]),
        ]);

        $product = Product::factory()->active()->create([
            'price' => 120,
            'stock' => 10,
        ]);

        $this->withSession(['cart.items' => [$product->id => 2]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect('https://sandbox.sslcommerz.com/EasyCheckOut/test-session');

        $order = Order::query()->firstOrFail();

        $this->assertSame('240.00', $order->total_amount);
        $this->assertSame('USD', $order->currency);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(2, $order->items()->sum('quantity'));
        $this->assertSame($order->id, session('checkout.order_id'));

        Http::assertSent(function (Request $request) use ($order): bool {
            $data = $request->data();

            return $request->url() === 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'
                && $data['tran_id'] === $order->tran_id
                && $data['total_amount'] === '240.00'
                && $data['currency'] === 'USD';
        });
    }

    public function test_checkout_is_blocked_when_sslcommerz_is_not_configured(): void
    {
        config()->set('services.sslcommerz.store_id', null);
        config()->set('services.sslcommerz.store_password', null);

        Http::preventStrayRequests();

        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_a_valid_payment_marks_the_order_paid_and_reduces_stock(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'TXN-PAID',
                'amount' => '100.00',
                'currency' => 'USD',
                'val_id' => 'VAL-123',
            ]),
        ]);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-PAID');

        $this->withSession(['checkout.order_id' => $order->id, 'cart.items' => [$product->id => 2]])
            ->post(route('payments.sslcommerz.success'), [
                'tran_id' => 'TXN-PAID',
                'val_id' => 'VAL-123',
            ])
            ->assertRedirect(route('checkout.confirmation', $order));

        $order->refresh();

        $this->assertTrue($order->isPaid());
        $this->assertSame('VAL-123', $order->val_id);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame([], session('cart.items', []));

        Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($order): bool {
            return $mail->hasTo($order->customer_email) && $mail->order->id === $order->id;
        });
    }

    public function test_a_payment_with_a_mismatched_amount_is_rejected(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'TXN-MISMATCH',
                'amount' => '99.00',
                'currency' => 'USD',
                'val_id' => 'VAL-999',
            ]),
        ]);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-MISMATCH');

        $this->post(route('payments.sslcommerz.success'), [
            'tran_id' => 'TXN-MISMATCH',
            'val_id' => 'VAL-999',
        ])->assertSessionHas('error');

        $order->refresh();

        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(10, $product->fresh()->stock);

        Mail::assertNothingSent();
    }

    public function test_the_checkout_page_shows_a_test_mode_notice(): void
    {
        config()->set('services.sslcommerz.test_mode', true);

        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('SSLCommerz test mode is on')
            ->assertDontSee('SSLCommerz is not configured yet');
    }

    public function test_checkout_can_be_completed_in_test_mode_without_credentials(): void
    {
        Mail::fake();
        config()->set('services.sslcommerz.store_id', null);
        config()->set('services.sslcommerz.store_password', null);
        config()->set('services.sslcommerz.test_mode', true);

        Http::preventStrayRequests();

        $product = Product::factory()->active()->create([
            'price' => 50,
            'stock' => 10,
        ]);

        $this->withSession(['cart.items' => [$product->id => 2]])
            ->post(route('checkout.store'), $this->checkoutPayload());

        $order = Order::query()->firstOrFail();

        $this->assertSame(OrderStatus::Pending, $order->status);

        $this->get(route('payments.sslcommerz.simulate', $order))
            ->assertOk()
            ->assertSee('Test mode')
            ->assertSee($order->order_number);

        $this->post(route('payments.sslcommerz.simulate.success', $order))
            ->assertRedirect(route('checkout.confirmation', $order));

        $order->refresh();

        $this->assertTrue($order->isPaid());
        $this->assertStringStartsWith('TEST-', $order->val_id);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame([], session('cart.items', []));

        Mail::assertSent(OrderConfirmation::class, function (OrderConfirmation $mail) use ($order): bool {
            return $mail->hasTo($order->customer_email) && $mail->order->id === $order->id;
        });
    }

    public function test_a_simulated_payment_can_be_declined(): void
    {
        Mail::fake();
        config()->set('services.sslcommerz.test_mode', true);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-SIMULATE-FAIL');

        $this->withSession(['checkout.order_id' => $order->id])
            ->post(route('payments.sslcommerz.simulate.fail', $order))
            ->assertRedirect(route('checkout.create'));

        $this->assertSame(OrderStatus::Failed, $order->fresh()->status);

        Mail::assertNothingSent();
    }

    public function test_a_simulated_payment_can_be_cancelled(): void
    {
        Mail::fake();
        config()->set('services.sslcommerz.test_mode', true);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-SIMULATE-CANCEL');

        $this->withSession(['checkout.order_id' => $order->id])
            ->post(route('payments.sslcommerz.simulate.cancel', $order))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);

        Mail::assertNothingSent();
    }

    public function test_simulated_payment_pages_are_hidden_when_test_mode_is_off(): void
    {
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-NO-TEST-MODE');

        $this->withSession(['checkout.order_id' => $order->id])
            ->get(route('payments.sslcommerz.simulate', $order))
            ->assertNotFound();
    }

    public function test_simulated_payment_requires_the_checkout_session(): void
    {
        config()->set('services.sslcommerz.test_mode', true);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-SIMULATE-GUEST');

        $this->get(route('payments.sslcommerz.simulate', $order))->assertNotFound();
    }

    public function test_an_unverified_failure_callback_does_not_change_the_order(): void
    {
        Mail::fake();
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-FAILED');

        $this->post(route('payments.sslcommerz.fail'), ['tran_id' => 'TXN-FAILED'])
            ->assertRedirect(route('checkout.create'))
            ->assertSessionHas('error');

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        Mail::assertNothingSent();
    }

    public function test_an_unverified_cancel_callback_does_not_change_the_order(): void
    {
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-CANCEL-CALLBACK');

        $this->post(route('payments.sslcommerz.cancel'), ['tran_id' => $order->tran_id])
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
    }

    public function test_a_valid_payment_can_follow_an_unverified_failure_callback(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'TXN-LATE-SUCCESS',
                'amount' => '100.00',
                'currency' => 'USD',
            ]),
        ]);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-LATE-SUCCESS');

        $this->post(route('payments.sslcommerz.fail'), ['tran_id' => $order->tran_id]);
        $this->post(route('payments.sslcommerz.ipn'), [
            'tran_id' => $order->tran_id,
            'val_id' => 'VAL-LATE',
        ])->assertOk();

        $this->assertTrue($order->fresh()->isPaid());
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_a_verified_callback_does_not_reveal_another_customers_order(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'TXN-OTHER-SESSION',
                'amount' => '100.00',
                'currency' => 'USD',
            ]),
        ]);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-OTHER-SESSION');

        $this->post(route('payments.sslcommerz.success'), [
            'tran_id' => $order->tran_id,
            'val_id' => 'VAL-OTHER',
        ])->assertRedirect(route('products.index'));

        $this->get(route('checkout.confirmation', $order))->assertNotFound();
        $this->assertTrue($order->fresh()->isPaid());
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_an_ipn_first_payment_clears_the_checkout_cart_on_browser_return(): void
    {
        Mail::fake();
        Http::preventStrayRequests();
        Http::fake([
            'sandbox.sslcommerz.com/validator/*' => Http::response([
                'status' => 'VALID',
                'tran_id' => 'TXN-IPN-FIRST',
                'amount' => '100.00',
                'currency' => 'USD',
            ]),
        ]);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-IPN-FIRST');

        $this->withSession(['checkout.order_id' => $order->id, 'cart.items' => [$product->id => 2]])
            ->post(route('payments.sslcommerz.ipn'), [
                'tran_id' => $order->tran_id,
                'val_id' => 'VAL-IPN',
            ])->assertOk();

        $this->post(route('payments.sslcommerz.success'), ['tran_id' => $order->tran_id])
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertSame([], session('cart.items', []));
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_repeated_simulated_success_sends_only_one_confirmation_email(): void
    {
        Mail::fake();
        config()->set('services.sslcommerz.test_mode', true);

        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-REPEATED');

        $this->withSession(['checkout.order_id' => $order->id])
            ->post(route('payments.sslcommerz.simulate.success', $order))
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->post(route('payments.sslcommerz.simulate.success', $order))
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertSame(8, $product->fresh()->stock);
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_the_confirmation_email_contains_the_order_details(): void
    {
        $product = Product::factory()->active()->create([
            'product_name' => 'Aurora Desk Lamp',
            'price' => 50,
            'stock' => 10,
        ]);
        $order = $this->createPendingOrder($product, 'TXN-MAIL-CONTENT');
        $order->load('items');

        $html = (new OrderConfirmation($order))->render();

        $this->assertStringContainsString($order->order_number, $html);
        $this->assertStringContainsString('Aurora Desk Lamp', $html);
        $this->assertStringContainsString($order->customer_name, $html);
    }

    public function test_the_confirmation_page_requires_the_checkout_session(): void
    {
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = $this->createPendingOrder($product, 'TXN-CONFIRM');

        $this->get(route('checkout.confirmation', $order))->assertNotFound();

        $this->withSession(['checkout.order_id' => $order->id])
            ->get(route('checkout.confirmation', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Thank you for your order');
    }

    /**
     * @return array<string, string>
     */
    private function checkoutPayload(): array
    {
        return [
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '01700000000',
            'shipping_address' => '12 Lake Road',
            'city' => 'Dhaka',
            'postcode' => '1205',
            'notes' => 'Leave at the front desk.',
        ];
    }

    private function createPendingOrder(Product $product, string $transactionId): Order
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Pending,
            'payment_gateway' => 'sslcommerz',
            'tran_id' => $transactionId,
            'total_amount' => (float) $product->price * 2,
            'currency' => 'USD',
            'paid_at' => null,
            'val_id' => null,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'unit_price' => $product->price,
            'quantity' => 2,
            'line_total' => (float) $product->price * 2,
        ]);

        return $order;
    }
}
