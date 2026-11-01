@extends('layouts.app')

@section('title', 'Test payment')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8 lg:py-14">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 bg-slate-900 px-6 py-5 sm:px-8">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-white/10 text-sm font-bold text-white">PC</span>
                        <div>
                            <p class="text-sm font-semibold text-white">SSLCommerz test checkout</p>
                            <p class="text-xs text-slate-400">No real payment will be taken</p>
                        </div>
                    </div>

                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-300 px-3 py-1 text-xs font-bold uppercase tracking-[0.12em] text-slate-900">
                        <span class="size-1.5 rounded-full bg-slate-900"></span>
                        Test mode
                    </span>
                </div>
            </div>

            <div class="p-6 sm:p-8">
                <p class="text-sm leading-relaxed text-slate-600">
                    This page stands in for the SSLCommerz hosted checkout. Choose an outcome below to test the full
                    order flow without store credentials. Simulated success marks the order as paid and reduces stock.
                </p>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">Order {{ $order->order_number }}</h2>
                        <span class="text-xs font-medium uppercase tracking-[0.12em] text-slate-400">{{ $order->currency }}</span>
                    </div>

                    <ul class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                        @foreach ($order->items as $item)
                            <li class="flex items-center justify-between gap-4">
                                <span class="min-w-0 text-slate-600">
                                    <span class="block truncate font-medium text-slate-900">{{ $item->product_name }}</span>
                                    <span class="text-xs text-slate-500">Qty {{ $item->quantity }} &middot; {{ \App\Support\Money::format($item->unit_price) }} each</span>
                                </span>
                                <span class="shrink-0 font-semibold text-slate-900">{{ \App\Support\Money::format($item->line_total) }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4 text-base font-semibold text-slate-900">
                        <span>Total</span>
                        <span>{{ \App\Support\Money::format($order->total_amount) }}</span>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <form method="POST" action="{{ route('payments.sslcommerz.simulate.success', $order) }}">
                        @csrf

                        <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="10" cy="10" r="8"/>
                                <path d="m6.5 10 2.5 2.5 4.5-5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            Simulate successful payment
                        </button>
                    </form>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <form method="POST" action="{{ route('payments.sslcommerz.simulate.fail', $order) }}">
                            @csrf

                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 text-sm font-semibold text-amber-900 transition hover:bg-amber-100">
                                <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <circle cx="10" cy="10" r="8"/>
                                    <path d="M10 6v5M10 14.5v.5" stroke-linecap="round"/>
                                </svg>
                                Simulate failed payment
                            </button>
                        </form>

                        <form method="POST" action="{{ route('payments.sslcommerz.simulate.cancel', $order) }}">
                            @csrf

                            <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                                Cancel payment
                            </button>
                        </form>
                    </div>
                </div>

                <p class="mt-6 text-center text-xs leading-relaxed text-slate-400">
                    Test mode is controlled by <code class="rounded bg-slate-100 px-1 py-0.5">SSLCOMMERZ_TEST_MODE</code>.
                    Turn it off and add your store credentials to use the real gateway.
                </p>
            </div>
        </div>
    </div>
@endsection
