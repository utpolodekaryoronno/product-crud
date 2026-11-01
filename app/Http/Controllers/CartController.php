<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index(CartService $cart): View
    {
        return view('cart.index', [
            'items' => $cart->items(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    /**
     * Add a product to the cart.
     */
    public function store(Request $request, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::query()
            ->active()
            ->find($validated['product_id']);

        if (! $product) {
            return back()->with('error', 'That product is no longer available.');
        }

        if ($product->is_out_of_stock) {
            return back()->with('error', "{$product->product_name} is out of stock.");
        }

        $cart->add($product, (int) ($validated['quantity'] ?? 1));

        return back()->with('status', "{$product->product_name} was added to your cart.");
    }

    /**
     * Update the quantity of a product in the cart.
     */
    public function update(Request $request, Product $product, CartService $cart): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart->update($product, (int) $validated['quantity']);

        return back()->with('status', 'Your cart was updated.');
    }

    /**
     * Remove a product from the cart.
     */
    public function destroy(Product $product, CartService $cart): RedirectResponse
    {
        $cart->remove($product);

        return back()->with('status', "{$product->product_name} was removed from your cart.");
    }

    /**
     * Remove every product from the cart.
     */
    public function clear(CartService $cart): RedirectResponse
    {
        $cart->clear();

        return redirect()
            ->route('products.index')
            ->with('status', 'Your cart is now empty.');
    }
}
