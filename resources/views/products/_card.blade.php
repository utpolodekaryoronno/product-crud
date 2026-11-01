<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="relative aspect-square overflow-hidden bg-slate-100">
            <img
                src="{{ $product->thumbnail_url }}"
                alt="{{ $product->product_name }}"
                loading="lazy"
                class="size-full object-cover transition duration-500 group-hover:scale-105"
            >

            @if ($product->is_out_of_stock)
                <span class="absolute left-3 top-3 rounded-full bg-slate-900/90 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
                    Sold out
                </span>
            @elseif ($product->stock <= 5)
                <span class="absolute left-3 top-3 rounded-full bg-amber-100/95 px-3 py-1 text-xs font-semibold text-amber-900 backdrop-blur">
                    Only {{ $product->stock }} left
                </span>
            @endif
        </div>

        <div class="p-5">
            @if ($product->category || $product->brand)
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                    @if ($product->category)
                        <span>{{ $product->category->category_name }}</span>
                    @endif
                    @if ($product->category && $product->brand)
                        <span aria-hidden="true">&middot;</span>
                    @endif
                    @if ($product->brand)
                        <span>{{ $product->brand->brand_name }}</span>
                    @endif
                </div>
            @endif

            <h2 class="mt-1.5 truncate text-base font-semibold tracking-tight text-slate-900">
                {{ $product->product_name }}
            </h2>
            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-slate-500">
                {{ $product->description }}
            </p>

            @if ($product->tags->isNotEmpty())
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($product->tags->take(2) as $tag)
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600">
                            {{ $tag->tag_name }}
                        </span>
                    @endforeach
                </div>
            @endif

            <div class="mt-4 flex items-center justify-between">
                <span class="text-lg font-semibold tracking-tight text-slate-900">
                    ${{ number_format((float) $product->price, 2) }}
                </span>
                <span class="inline-flex items-center gap-1 text-sm font-medium text-slate-500 transition group-hover:text-slate-900">
                    View
                    <svg class="size-4 transition group-hover:translate-x-0.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M4 10h12M11 5l5 5-5 5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
            </div>
        </div>
    </a>
</article>
