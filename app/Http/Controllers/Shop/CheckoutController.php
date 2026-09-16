<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Services\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    private const TAX_RATE = 0.08;

    public function create(Request $request, Cart $cart): Response|RedirectResponse
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $request->user()->loadMissing('profile');

        return Inertia::render('Shop/Checkout', [
            'items' => $items->values(),
            'subtotal' => $cart->subtotal(),
            'taxRate' => self::TAX_RATE,
            'profile' => $request->user()->profile,
        ]);
    }

    public function store(Request $request, Cart $cart): RedirectResponse
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $items = $cart->items();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        foreach ($items as $item) {
            if ($item['quantity'] > $item['product']->quantity) {
                return redirect()->route('cart.index')->withErrors([
                    'quantity' => "Not enough stock for \"{$item['product']->name}\" — only {$item['product']->quantity} left.",
                ]);
            }
        }

        $order = DB::transaction(function () use ($items, $validated, $request) {
            $subtotal = round($items->sum('total'), 2);
            $tax = round($subtotal * self::TAX_RATE, 2);
            $total = round($subtotal + $tax, 2);

            $order = SalesOrder::create([
                'order_number' => 'ORD-'.strtoupper(Str::random(10)),
                'user_id' => $request->user()->id,
                'status' => SalesOrder::STATUS_PENDING,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_cost' => 0,
                'total' => $total,
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = $item['product'];

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total' => $item['total'],
                ]);

                $product->decrement('quantity', $item['quantity']);
            }

            return $order;
        });

        $cart->clear();

        return redirect()->route('orders.show', $order)->with('success', 'Order placed! Thank you for your purchase.');
    }
}
