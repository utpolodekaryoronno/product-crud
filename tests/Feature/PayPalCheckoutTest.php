<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PayPalCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.paypal.client_id', 'sandbox-client');
        config()->set('services.paypal.client_secret', 'sandbox-secret');
        config()->set('services.paypal.sandbox', true);
        config()->set('shop.currency', 'USD');
    }

    public function test_paypal_checkout_creates_a_pending_order_and_redirects_to_approval(): void
    {
        $this->fakePayPalCreation();
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 2]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-123');

        $order = Order::query()->firstOrFail();
        $this->assertSame('paypal', $order->payment_gateway);
        $this->assertSame('PAYPAL-123', $order->paypal_order_id);
        $this->assertSame('100.00', $order->total_amount);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame([$product->id => 2], session('cart.items'));
        Http::assertSentCount(2);
    }

    public function test_paypal_checkout_is_unavailable_without_credentials(): void
    {
        config()->set('services.paypal.client_id', null);
        Http::preventStrayRequests();
        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_paypal_checkout_rejects_zero_decimal_currencies_before_creating_an_order(): void
    {
        config()->set('shop.currency', 'JPY');
        Http::preventStrayRequests();
        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_paypal_checkout_rejects_bdt_before_creating_an_order(): void
    {
        config()->set('shop.currency', 'BDT');
        Http::preventStrayRequests();
        $product = Product::factory()->active()->create(['stock' => 10]);

        $this->withSession(['cart.items' => [$product->id => 1]])
            ->post(route('checkout.store'), $this->checkoutPayload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_paypal_requires_the_customer_checkout_session_to_capture(): void
    {
        Http::preventStrayRequests();
        $order = $this->pendingPayPalOrder();

        $this->get(route('payments.paypal.return', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_paypal_only_marks_the_order_paid_after_a_matching_capture(): void
    {
        Mail::fake();
        $order = $this->pendingPayPalOrder();
        $this->fakePayPalCapture($order);

        $this->withSession(['checkout.order_id' => $order->id, 'cart.items' => [$order->items->first()->product_id => 2]])
            ->get(route('payments.paypal.return', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('CAPTURE-123', $order->fresh()->val_id);
        $this->assertSame(8, $order->items->first()->product->fresh()->stock);
        $this->assertSame([], session('cart.items', []));
        Mail::assertSent(OrderConfirmation::class, $order->customer_email);
    }

    public function test_paypal_does_not_mark_a_mismatched_capture_paid(): void
    {
        Mail::fake();
        $order = $this->pendingPayPalOrder();
        $this->fakePayPalCapture($order, '0.01');

        $this->withSession(['checkout.order_id' => $order->id])
            ->get(route('payments.paypal.return', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(10, $order->items->first()->product->fresh()->stock);
        Mail::assertNothingSent();
    }

    public function test_repeated_paypal_return_does_not_capture_or_email_twice(): void
    {
        Mail::fake();
        $order = $this->pendingPayPalOrder();
        $this->fakePayPalCapture($order);
        $this->withSession(['checkout.order_id' => $order->id]);

        $this->get(route('payments.paypal.return', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('checkout.confirmation', $order));
        $this->get(route('payments.paypal.return', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('checkout.confirmation', $order));

        $this->assertSame(8, $order->items->first()->product->fresh()->stock);
        Http::assertSentCount(2);
        Mail::assertSent(OrderConfirmation::class, 1);
    }

    public function test_paypal_cancel_preserves_the_order_and_cart(): void
    {
        $order = $this->pendingPayPalOrder();
        $productId = $order->items->first()->product_id;

        $this->withSession(['checkout.order_id' => $order->id, 'cart.items' => [$productId => 2]])
            ->get(route('payments.paypal.cancel', ['token' => $order->paypal_order_id]))
            ->assertRedirect(route('cart.index'));

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame([$productId => 2], session('cart.items'));
    }

    /** @return array<string, string> */
    private function checkoutPayload(): array
    {
        return [
            'payment_method' => 'paypal',
            'customer_name' => 'PayPal Buyer',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '01700000000',
            'shipping_address' => '12 Lake Road',
            'city' => 'Dhaka',
        ];
    }

    private function pendingPayPalOrder(): Order
    {
        $product = Product::factory()->active()->create(['price' => 50, 'stock' => 10]);
        $order = Order::factory()->create([
            'payment_gateway' => 'paypal',
            'paypal_order_id' => 'PAYPAL-123',
            'total_amount' => 100,
            'currency' => 'USD',
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->product_name,
            'unit_price' => 50,
            'quantity' => 2,
            'line_total' => 100,
        ]);

        return $order->load('items.product');
    }

    private function fakePayPalCreation(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-123',
                'status' => 'PAYER_ACTION_REQUIRED',
                'links' => [
                    ['rel' => 'payer-action', 'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-123'],
                ],
            ], 201),
        ]);
    }

    private function fakePayPalCapture(Order $order, string $value = '100.00'): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response(['access_token' => 'test-token']),
            'api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-123/capture' => Http::response([
                'id' => 'PAYPAL-123',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'reference_id' => $order->order_number,
                    'custom_id' => $order->tran_id,
                    'amount' => ['currency_code' => 'USD', 'value' => '100.00'],
                    'payments' => ['captures' => [[
                        'id' => 'CAPTURE-123',
                        'status' => 'COMPLETED',
                        'final_capture' => true,
                        'amount' => ['currency_code' => 'USD', 'value' => $value],
                    ]]],
                ]],
            ]),
        ]);
    }
}
