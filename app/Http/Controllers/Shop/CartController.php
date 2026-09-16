<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Cart $cart): Response
    {
        return Inertia::render('Shop/Cart', [
            'items' => $cart->items()->values(),
            'subtotal' => $cart->subtotal(),
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($validated['product_id']);

        abort_unless($product->is_active, 404);

        $alreadyInCart = $cart->contents()[$product->id] ?? 0;
        $quantity = min($validated['quantity'], max(0, $product->quantity - $alreadyInCart));

        if ($quantity < 1) {
            return back()->withErrors(['quantity' => 'Not enough stock available.']);
        }

        $cart->add($product->id, $quantity);

        return back()->with('success', "{$product->name} added to your cart.");
    }

    public function update(Request $request, Product $product, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $quantity = min($validated['quantity'], $product->quantity);

        $cart->setQuantity($product->id, $quantity);

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Product $product, Cart $cart): RedirectResponse
    {
        $cart->remove($product->id);

        return back()->with('success', 'Item removed from cart.');
    }
}
