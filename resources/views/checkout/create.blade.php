@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('cart.index') }}" class="transition hover:text-slate-900">Cart</a>
            <svg class="size-4 text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="font-medium text-slate-900">Checkout</span>
        </nav>

        <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">Checkout</h1>
        <p class="mt-1 text-sm text-slate-500">Enter your delivery details and pay securely with SSLCommerz.</p>

        @unless ($gatewayConfigured)
            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                <p class="font-semibold">SSLCommerz is not configured yet</p>
                <p class="mt-1">
                    Add <code class="rounded bg-amber-100 px-1 py-0.5">SSLCOMMERZ_STORE_ID</code> and
                    <code class="rounded bg-amber-100 px-1 py-0.5">SSLCOMMERZ_STORE_PASSWORD</code> to your
                    <code class="rounded bg-amber-100 px-1 py-0.5">.env</code> file, then run
                    <code class="rounded bg-amber-100 px-1 py-0.5">php artisan config:clear</code>.
                    The payment buttons stay disabled until the gateway is ready.
                </p>
            </div>
        @endunless

        <form method="POST" action="{{ route('checkout.store') }}" class="mt-8 grid grid-cols-1 gap-8 lg:grid-cols-[minmax(0,1fr)_380px]">
            @csrf

            <div class="space-y-6">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold tracking-tight text-slate-900">Contact details</h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="customer_name" class="mb-1.5 block text-sm font-medium text-slate-700">Full name</label>
                            <input id="customer_name" type="text" name="customer_name" value="{{ old('customer_name') }}" required autocomplete="name" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="customer_email" class="mb-1.5 block text-sm font-medium text-slate-700">Email address</label>
                            <input id="customer_email" type="email" name="customer_email" value="{{ old('customer_email') }}" required autocomplete="email" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="customer_phone" class="mb-1.5 block text-sm font-medium text-slate-700">Phone number</label>
                            <input id="customer_phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required autocomplete="tel" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-base font-semibold tracking-tight text-slate-900">Shipping address</h2>

                    <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="shipping_address" class="mb-1.5 block text-sm font-medium text-slate-700">Street address</label>
                            <input id="shipping_address" type="text" name="shipping_address" value="{{ old('shipping_address') }}" required autocomplete="street-address" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="city" class="mb-1.5 block text-sm font-medium text-slate-700">City</label>
                            <input id="city" type="text" name="city" value="{{ old('city') }}" required autocomplete="address-level2" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div>
                            <label for="postcode" class="mb-1.5 block text-sm font-medium text-slate-700">Postcode <span class="font-normal text-slate-400">(optional)</span></label>
                            <input id="postcode" type="text" name="postcode" value="{{ old('postcode') }}" autocomplete="postal-code" class="h-11 w-full rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="notes" class="mb-1.5 block text-sm font-medium text-slate-700">Order notes <span class="font-normal text-slate-400">(optional)</span></label>
                            <textarea id="notes" name="notes" rows="3" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none focus:ring-2 focus:ring-slate-200">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-24">
                <h2 class="text-base font-semibold tracking-tight text-slate-900">Order summary</h2>

                <ul class="mt-4 space-y-3">
                    @foreach ($items as $item)
                        @php($product = $item['product'])

                        <li class="flex items-start gap-3">
                            <span class="block size-14 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                <img src="{{ $product->thumbnail_url }}" alt="" class="size-full object-cover">
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-medium text-slate-900">{{ $product->product_name }}</span>
                                <span class="block text-xs text-slate-500">Qty {{ $item['quantity'] }} &middot; {{ \App\Support\Money::format($product->price) }} each</span>
                            </span>

                            <span class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($item['line_total']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-2 border-t border-slate-200 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Subtotal</dt>
                        <dd class="font-medium text-slate-900">{{ \App\Support\Money::format($subtotal) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-slate-500">Shipping</dt>
                        <dd class="font-medium text-emerald-600">Free</dd>
                    </div>
                </dl>

                <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 text-base font-semibold text-slate-900">
                    <span>Total</span>
                    <span>{{ \App\Support\Money::format($subtotal) }} <span class="text-xs font-medium text-slate-400">{{ $currency }}</span></span>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <svg class="size-4 text-emerald-600" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path d="M10 2.5 4 5v5c0 3.4 2.4 6.2 6 7.5 3.6-1.3 6-4.1 6-7.5V5l-6-2.5Z" stroke-linejoin="round"/>
                            <path d="m7.5 10 1.8 1.8 3.4-3.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        SSLCommerz secure checkout
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Pay with Visa, Mastercard, bKash, Nagad, Rocket or internet banking. You will be redirected to SSLCommerz to finish the payment.
                    </p>
                </div>

                <button
                    type="submit"
                    @disabled(! $gatewayConfigured)
                    class="mt-5 inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500"
                >
                    Pay {{ \App\Support\Money::format($subtotal) }}
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                <a href="{{ route('cart.index') }}" class="mt-3 inline-flex h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                    Back to cart
                </a>
            </aside>
        </form>
    </div>
@endsection
