@extends('layouts.app')

@section('title', $product->product_name)
@section('description', \Illuminate\Support\Str::limit($product->description, 155))

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
        <nav class="flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('products.index') }}" class="transition hover:text-slate-900">Shop</a>
            @if ($product->category)
                <svg class="size-4 text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                    <path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <a href="{{ route('products.index', ['category' => $product->category->category_slug]) }}" class="transition hover:text-slate-900">
                    {{ $product->category->category_name }}
                </a>
            @endif
            <svg class="size-4 text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="m8 5 5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="truncate font-medium text-slate-900">{{ $product->product_name }}</span>
        </nav>

        <div class="mt-6 grid grid-cols-1 gap-8 lg:grid-cols-2 lg:gap-12">
            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <img
                    src="{{ $product->thumbnail_url }}"
                    alt="{{ $product->product_name }}"
                    class="aspect-square w-full object-cover"
                >
            </div>

            <div class="flex flex-col justify-center">
                <div class="flex flex-wrap items-center gap-3">
                    @if ($product->category)
                        <a href="{{ route('products.index', ['category' => $product->category->category_slug]) }}" class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-slate-600 transition hover:bg-slate-200">
                            {{ $product->category->category_name }}
                        </a>
                    @endif

                    @if ($product->is_out_of_stock)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                            <span class="size-1.5 rounded-full bg-rose-500"></span>
                            Out of stock
                        </span>
                    @elseif ($product->stock <= 5)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                            <span class="size-1.5 rounded-full bg-amber-500"></span>
                            Low stock &middot; {{ $product->stock }} left
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            In stock &middot; {{ $product->stock }} available
                        </span>
                    @endif
                </div>

                <h1 class="mt-4 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">
                    {{ $product->product_name }}
                </h1>

                @if ($product->brand)
                    <p class="mt-2 text-sm text-slate-500">
                        by
                        <a href="{{ route('products.index', ['brand' => $product->brand->brand_slug]) }}" class="font-semibold text-slate-900 underline-offset-4 transition hover:underline">
                            {{ $product->brand->brand_name }}
                        </a>
                    </p>
                @endif

                @if ($product->tags->isNotEmpty())
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($product->tags as $tag)
                            <a href="{{ route('products.index', ['tag' => $tag->tag_slug]) }}" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 transition hover:bg-slate-200">
                                {{ $tag->tag_name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-900">
                    ${{ number_format((float) $product->price, 2) }}
                </p>

                <div class="mt-6 border-t border-slate-200 pt-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Description</h2>
                    <p class="mt-3 text-base leading-relaxed text-slate-600">
                        {{ $product->description }}
                    </p>
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 bg-white p-5 text-sm">
                    <div>
                        <dt class="text-slate-500">SKU</dt>
                        <dd class="mt-1 font-semibold text-slate-900">PC-{{ str_pad((string) $product->id, 5, '0', STR_PAD_LEFT) }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Availability</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $product->stock_label }}</dd>
                    </div>
                    @if ($product->brand)
                        <div>
                            <dt class="text-slate-500">Brand</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $product->brand->brand_name }}</dd>
                        </div>
                    @endif
                    @if ($product->category)
                        <div>
                            <dt class="text-slate-500">Category</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $product->category->category_name }}</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-slate-500">Shipping</dt>
                        <dd class="mt-1 font-semibold text-slate-900">Free over $75</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Returns</dt>
                        <dd class="mt-1 font-semibold text-slate-900">30 days</dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('products.index') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 text-sm font-semibold text-white transition hover:bg-slate-800">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path d="M16 10H4M9 5l-5 5 5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Back to all products
                    </a>
                    <a href="mailto:hello@example.com?subject={{ urlencode('Question about '.$product->product_name) }}" class="inline-flex h-12 items-center justify-center rounded-xl border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:text-slate-900">
                        Ask a question
                    </a>
                </div>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-16 border-t border-slate-200 pt-10">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-semibold tracking-tight text-slate-900">You may also like</h2>
                    <a href="{{ route('products.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-slate-900">View all</a>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relatedProducts as $relatedProduct)
                        @include('products._card', ['product' => $relatedProduct])
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
