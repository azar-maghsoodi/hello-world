<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load('categories', 'attributes');

        return Inertia::render('Shop/Product', [
            'product' => $product,
        ]);
    }
}
