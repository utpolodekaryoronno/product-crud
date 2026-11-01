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
            <h2 class="truncate text-base font-semibold tracking-tight text-slate-900">
                {{ $product->product_name }}
            </h2>
            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-slate-500">
                {{ $product->description }}
            </p>

            <div class="mt-5 flex items-center justify-between">
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
