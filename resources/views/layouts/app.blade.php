<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Shop') &mdash; {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'A small, carefully curated collection of everyday products.')">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col bg-slate-50 text-slate-900 antialiased">
    <header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('products.index') }}" class="flex items-center gap-3">
                <span class="grid size-10 place-items-center rounded-xl bg-slate-900 text-sm font-bold tracking-tight text-white">PC</span>
                <span class="leading-tight">
                    <span class="block text-sm font-semibold tracking-tight text-slate-900">{{ config('app.name') }}</span>
                    <span class="block text-[11px] font-medium uppercase tracking-[0.18em] text-slate-500">Everyday essentials</span>
                </span>
            </a>

            <nav class="flex items-center gap-1 text-sm font-medium">
                <a
                    href="{{ route('products.index') }}"
                    @class([
                        'rounded-lg px-3 py-2 transition',
                        'bg-slate-100 text-slate-900' => request()->routeIs('products.*'),
                        'text-slate-500 hover:text-slate-900' => ! request()->routeIs('products.*'),
                    ])
                >
                    Shop
                </a>
                <a
                    href="{{ route('cart.index') }}"
                    @class([
                        'flex items-center gap-2 rounded-lg px-3 py-2 transition',
                        'bg-slate-100 text-slate-900' => request()->routeIs('cart.*', 'checkout.*'),
                        'text-slate-500 hover:text-slate-900' => ! request()->routeIs('cart.*', 'checkout.*'),
                    ])
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path d="M3 4h2l2.4 10.2a2 2 0 0 0 2 1.6h7.7a2 2 0 0 0 2-1.6L21 8H6" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="9.5" cy="20" r="1.4"/>
                        <circle cx="17.5" cy="20" r="1.4"/>
                    </svg>
                    Cart
                    @if (($cartCount ?? 0) > 0)
                        <span class="grid min-w-5 place-items-center rounded-full bg-amber-300 px-1.5 py-0.5 text-[11px] font-bold text-slate-900">{{ $cartCount }}</span>
                    @endif
                </a>
                <a
                    href="{{ url('/admin') }}"
                    class="rounded-lg px-3 py-2 text-slate-500 transition hover:text-slate-900"
                >
                    Admin
                </a>
            </nav>
        </div>
    </header>

    @if (session('status') || session('error') || $errors->any())
        <div class="mx-auto w-full max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="10" cy="10" r="8"/>
                        <path d="m6.5 10 2.5 2.5 4.5-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            @if (session('error'))
                <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <svg class="mt-0.5 size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="10" cy="10" r="8"/>
                        <path d="M10 6v5M10 14.5v.5" stroke-linecap="round"/>
                    </svg>
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <p class="font-semibold">Please check the form below.</p>
                    <ul class="mt-1 list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="grid size-9 place-items-center rounded-lg bg-slate-900 text-xs font-bold text-white">PC</span>
                <p class="text-sm text-slate-500">
                    &copy; {{ now()->year }} {{ config('app.name') }}. Built with Laravel &amp; Filament.
                </p>
            </div>

            <div class="flex items-center gap-5 text-sm font-medium text-slate-500">
                <a href="{{ route('products.index') }}" class="transition hover:text-slate-900">Products</a>
                <a href="{{ url('/admin') }}" class="transition hover:text-slate-900">Dashboard</a>
            </div>
        </div>
    </footer>
</body>
</html>
