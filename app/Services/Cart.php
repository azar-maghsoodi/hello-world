<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    private const SESSION_KEY = 'cart';

    /**
     * Raw product_id => quantity map from the session.
     */
    public function contents(): array
    {
        return session(self::SESSION_KEY, []);
    }

    public function add(int $productId, int $quantity): void
    {
        $cart = $this->contents();
        $cart[$productId] = max(0, ($cart[$productId] ?? 0) + $quantity);
        session([self::SESSION_KEY => $cart]);
    }

    public function setQuantity(int $productId, int $quantity): void
    {
        $cart = $this->contents();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(int $productId): void
    {
        $this->setQuantity($productId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->contents());
    }

    /**
     * Resolved cart lines, each: ['product' => Product, 'quantity' => int, 'unit_price' => float, 'total' => float].
     * Products that no longer exist or are inactive are silently dropped.
     */
    public function items(): Collection
    {
        $cart = $this->contents();

        if (empty($cart)) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', array_keys($cart))
            ->where('is_active', true)
            ->get()
            ->map(function (Product $product) use ($cart) {
                $quantity = $cart[$product->id];
                $unitPrice = (float) ($product->sale_price ?? $product->price);

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total' => round($unitPrice * $quantity, 2),
                ];
            });
    }

    public function subtotal(): float
    {
        return round($this->items()->sum('total'), 2);
    }
}
