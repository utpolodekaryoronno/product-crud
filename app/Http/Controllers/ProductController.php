<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
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
        $brandSlug = trim((string) $request->query('brand', ''));
        $categorySlug = trim((string) $request->query('category', ''));
        $tagSlug = trim((string) $request->query('tag', ''));

        $products = Product::query()
            ->with(['brand', 'category', 'tags'])
            ->active()
            ->search($search)
            ->forBrand($brandSlug)
            ->forCategory($categorySlug)
            ->forTag($tagSlug)
            ->when($sort === 'price_asc', fn (Builder $query) => $query->orderBy('price'))
            ->when($sort === 'price_desc', fn (Builder $query) => $query->orderByDesc('price'))
            ->when($sort === 'name', fn (Builder $query) => $query->orderBy('product_name'))
            ->when(! in_array($sort, ['price_asc', 'price_desc', 'name'], true), fn (Builder $query) => $query->latest())
            ->paginate(9)
            ->withQueryString();

        $brands = Brand::query()
            ->active()
            ->orderBy('brand_name')
            ->get();

        $categories = Category::query()
            ->active()
            ->orderBy('category_name')
            ->get();

        $tags = Tag::query()
            ->orderBy('tag_name')
            ->get();

        return view('products.index', [
            'products' => $products,
            'brands' => $brands,
            'categories' => $categories,
            'tags' => $tags,
            'search' => $search,
            'sort' => $sort,
            'brandSlug' => $brandSlug,
            'categorySlug' => $categorySlug,
            'tagSlug' => $tagSlug,
        ]);
    }

    /**
     * Display a single product.
     */
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['brand', 'category', 'tags']);

        $relatedProducts = Product::query()
            ->with(['brand', 'category', 'tags'])
            ->active()
            ->whereKeyNot($product->getKey())
            ->when($product->category_id, fn (Builder $query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->limit(3)
            ->get();

        if ($relatedProducts->count() < 3) {
            $relatedProducts = $relatedProducts->concat(
                Product::query()
                    ->with(['brand', 'category', 'tags'])
                    ->active()
                    ->whereKeyNot($product->getKey())
                    ->whereNotIn('id', $relatedProducts->pluck('id'))
                    ->latest()
                    ->limit(3 - $relatedProducts->count())
                    ->get(),
            );
        }

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
