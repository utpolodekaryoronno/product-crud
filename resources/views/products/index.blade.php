@extends('layouts.app')

@section('title', 'Shop')
@section('description', 'Browse our curated collection of thoughtfully designed everyday products.')

@section('content')
    <section class="relative isolate overflow-hidden bg-slate-900">
        <div class="pointer-events-none absolute -right-24 -top-24 size-96 rounded-full bg-amber-400/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-32 -left-24 size-96 rounded-full bg-sky-400/10 blur-3xl" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-200">
                <span class="size-1.5 rounded-full bg-amber-300"></span>
                Free shipping over $75
            </span>

            <h1 class="mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-white sm:text-5xl lg:text-6xl">
                Thoughtful products for everyday living.
            </h1>
            <p class="mt-5 max-w-2xl text-base leading-relaxed text-slate-300 sm:text-lg">
                A small, carefully curated collection. Every item is chosen for how it feels, how it lasts, and how quietly it fits into your day.
            </p>

            <form method="GET" action="{{ route('products.index') }}" class="mt-9 flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                    </svg>
                    <label for="search" class="sr-only">Search products</label>
                    <input
                        id="search"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search products by name or description..."
                        class="h-12 w-full rounded-xl border-0 bg-white pl-12 pr-4 text-sm text-slate-900 shadow-lg ring-1 ring-white/20 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-300"
                    >
                </div>

                <div class="sm:w-48">
                    <label for="sort" class="sr-only">Sort products</label>
                    <select
                        id="sort"
                        name="sort"
                        onchange="this.form.submit()"
                        class="h-12 w-full rounded-xl border-0 bg-white px-4 text-sm font-medium text-slate-700 shadow-lg ring-1 ring-white/20 focus:outline-none focus:ring-2 focus:ring-amber-300"
                    >
                        <option value="newest" @selected($sort === 'newest')>Newest first</option>
                        <option value="price_asc" @selected($sort === 'price_asc')>Price: low to high</option>
                        <option value="price_desc" @selected($sort === 'price_desc')>Price: high to low</option>
                        <option value="name" @selected($sort === 'name')>Name: A to Z</option>
                    </select>
                </div>

                <button type="submit" class="h-12 rounded-xl bg-amber-300 px-6 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-900">
                    Search
                </button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
                    @if ($search !== '')
                        Results for &ldquo;{{ $search }}&rdquo;
                    @else
                        Shop all products
                    @endif
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                    @if ($products->total() > 0)
                        &middot; showing {{ $products->firstItem() }}&ndash;{{ $products->lastItem() }}
                    @endif
                </p>
            </div>

            @if ($search !== '' || $sort !== 'newest')
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-900">
                    Reset filters
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M6 6l8 8M14 6l-8 8" stroke-linecap="round"/>
                    </svg>
                </a>
            @endif
        </div>

        @if ($products->isEmpty())
            <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m20 20-3.5-3.5" stroke-linecap="round"/>
                    </svg>
                </span>
                <h3 class="mt-4 text-base font-semibold text-slate-900">No products found</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                    We could not find anything matching your search. Try a different keyword or browse the full collection.
                </p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-flex h-10 items-center rounded-lg bg-slate-900 px-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Browse all products
                </a>
            </div>
        @else
            <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($products as $product)
                    @include('products._card', ['product' => $product])
                @endforeach
            </div>

            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @endif
    </section>
@endsection
