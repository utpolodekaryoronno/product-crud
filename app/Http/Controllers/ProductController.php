<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display the storefront product catalogue.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'newest');

        $products = Product::query()
            ->active()
            ->search($search)
            ->when($sort === 'price_asc', fn (Builder $query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when($sort === 'name', fn (Builder $query) => $query->orderBy('product_name'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'name'], true), fn (Builder $query) => $query->latest())
            ->paginate(9)
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    /**
     * Display a single product.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $relatedProducts = Product::query()
            ->active()
            ->whereKeyNot($product->getKey())
            ->latest()
            ->limit(3)
            ->get();

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
