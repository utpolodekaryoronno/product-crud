@extends('layouts.app')

@section('title', 'Your cart')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Your cart</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $items->sum('quantity')) }} ready for checkout.
                </p>
            </div>

            @if ($items->isNotEmpty())
                <form method="POST" action="{{ route('cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-slate-500 transition hover:text-rose-600">
                        Clear cart
                    </button>
                </form>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M3 4h2l2.4 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="20" r="1.4"/>
                        <circle cx="17.5" cy="20" r="1.4"/>
                    </svg>
                </span>
                <h2 class="mt-4 text-base font-semibold text-slate-900">Your cart is empty</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                    Browse the collection and add something you will actually use every day.
                </p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Start shopping
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
                <div class="space-y-4">
                    @foreach ($items as $item)
                        @php($product = $item['product'])

                        <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
                            <a href="{{ route('products.show', $product) }}" class="block size-24 shrink-0 overflow-hidden rounded-xl bg-slate-100">
                                <img src="{{ $product->thumbnail_url }}" alt="{{ $product->product_name }}" class="size-full object-cover">
                            </a>

                            <div class="flex min-w-0 flex-1 flex-col">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        @if ($product->brand)
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                                                {{ $product->brand->brand_name }}
                                            </p>
                                        @endif
                                        <a href="{{ route('products.show', $product) }}" class="mt-0.5 block truncate text-base font-semibold tracking-tight text-slate-900 transition hover:text-slate-600">
                                            {{ $product->product_name }}
                                        </a>
                                        <p class="mt-1 text-sm text-slate-500">
                                            {{ \App\Support\Money::format($product->price) }} each
                                        </p>
                                    </div>

                                    <p class="shrink-0 text-base font-semibold text-slate-900">
                                        {{ \App\Support\Money::format($item['line_total']) }}
                                    </p>
                                </div>

                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4">
                                    <form method="POST" action="{{ route('cart.update', $product) }}" class="flex items-center gap-2">
                                        @csrf
                                        @method('PATCH')

                                        <label for="quantity-{{ $product->id }}" class="sr-only">Quantity for {{ $product->product_name }}</label>
                                        <input
                                            id="quantity-{{ $product->id }}"
                                            type="number"
                                            name="quantity"
                                            value="{{ $item['quantity'] }}"
                                            min="1"
                                            max="{{ $product->stock }}"
                                            class="h-9 w-16 rounded-lg border border-slate-300 bg-white px-2 text-center text-sm font-semibold text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                        >
                                        <button type="submit" class="h-9 rounded-lg border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                                            Update
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('cart.destroy', $product) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm font-medium text-slate-500 transition hover:text-rose-600">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                    <h2 class="text-base font-semibold tracking-tight text-slate-900">Order summary</h2>

                    <dl class="mt-4 space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Subtotal</dt>
                            <dd class="font-medium text-slate-900">{{ \App\Support\Money::format($subtotal) }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-slate-500">Shipping</dt>
                            <dd class="text-slate-500">Free over {{ \App\Support\Money::format(75) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 text-base font-semibold text-slate-900">
                        <span>Total</span>
                        <span>{{ \App\Support\Money::format($subtotal) }}</span>
                    </div>

                    <a href="{{ route('checkout.create') }}" class="mt-6 inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Checkout with SSLCommerz
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>

                    <a href="{{ route('products.index') }}" class="mt-3 inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                        Continue shopping
                    </a>

                    <p class="mt-4 text-center text-xs text-slate-400">
                        Secure payment by cards, bKash, Nagad and internet banking.
                    </p>
                </aside>
            </div>
        @endif
    </div>
@endsection
