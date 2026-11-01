<x-mail::message>
# Thanks for your order, {{ $order->customer_name }}

We have received your payment of **{{ \App\Support\Money::format($order->total_amount) }}** for order **{{ $order->order_number }}**.

<x-mail::table>
| Product | Qty | Price | Total |
| :--- | :---: | ---: | ---: |
@foreach ($order->items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ \App\Support\Money::format($item->unit_price) }} | {{ \App\Support\Money::format($item->line_total) }} |
@endforeach
</x-mail::table>

**Total paid:** {{ \App\Support\Money::format($order->total_amount) }} {{ $order->currency }}

@if ($order->val_id && str_starts_with($order->val_id, 'TEST-'))
> This was a test payment. No money was taken.
@endif

### Delivery address

{{ $order->customer_name }}<br>
{{ $order->shipping_address }}<br>
{{ $order->city }}@if ($order->postcode), {{ $order->postcode }}@endif<br>
{{ $order->customer_phone }}

<x-mail::button :url="route('products.index')">
Continue shopping
</x-mail::button>

If you have any questions about this order, reply to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
