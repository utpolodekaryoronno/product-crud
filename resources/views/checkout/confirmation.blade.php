@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-100 text-emerald-600">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m5 12.5 4.5 4.5L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-600">{{ $order->status->label() }}</p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">Thank you for your order</h1>
                        <p class="mt-1 text-sm text-slate-500">
                            Your order details will be emailed to <span class="font-semibold text-slate-700">{{ $order->customer_email }}</span>.
                        </p>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-50 px-4 py-3 text-right">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Order number</p>
                    <p class="mt-1 font-mono text-sm font-semibold text-slate-900">{{ $order->order_number }}</p>
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-6 border-t border-slate-200 pt-6 sm:grid-cols-2">
                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Deliver to</h2>
                    <address class="mt-2 text-sm not-italic leading-relaxed text-slate-600">
                        <span class="block font-semibold text-slate-900">{{ $order->customer_name }}</span>
                        <span class="block">{{ $order->shipping_address }}</span>
                        <span class="block">{{ $order->city }}{{ $order->postcode ? ', '.$order->postcode : '' }}</span>
                        <span class="mt-1 block">{{ $order->customer_phone }}</span>
                    </address>
                </div>

                <div>
                    <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Payment</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                        <span class="block font-semibold text-slate-900">SSLCommerz</span>
                        @if ($order->paid_at)
                            <span class="block">Paid {{ $order->paid_at->format('d M Y, H:i') }}</span>
                        @endif
                        @if ($order->val_id)
                            <span class="mt-1 block font-mono text-xs text-slate-400">{{ $order->val_id }}</span>
                        @endif
                        @if ($order->val_id && str_starts_with($order->val_id, 'TEST-'))
                            <span class="mt-2 inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                Test payment &middot; no money was taken
                            </span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Items</h2>

                <ul class="mt-3 divide-y divide-slate-100">
                    @foreach ($order->items as $item)
                        <li class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-900">{{ $item->product_name }}</p>
                                <p class="text-xs text-slate-500">
                                    Qty {{ $item->quantity }} &middot; {{ \App\Support\Money::format($item->unit_price) }} each
                                </p>
                            </div>
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($item->line_total) }}</p>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 text-base font-semibold text-slate-900">
                    <span>Total paid</span>
                    <span>{{ \App\Support\Money::format($order->total_amount) }} <span class="text-xs font-medium text-slate-400">{{ $order->currency }}</span></span>
                </div>
            </div>

            @if ($order->notes)
                <div class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Your notes</p>
                    <p class="mt-1">{{ $order->notes }}</p>
                </div>
            @endif

            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('products.index') }}" class="inline-flex h-12 flex-1 items-center justify-center rounded-xl bg-slate-900 px-6 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Continue shopping
                </a>
                <a href="mailto:hello@example.com?subject={{ urlencode('Question about order '.$order->order_number) }}" class="inline-flex h-12 flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                    Ask about this order
                </a>
            </div>
        </div>
    </div>
@endsection
