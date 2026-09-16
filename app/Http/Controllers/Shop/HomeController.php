<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Shop/Home', [
            'categories' => Category::query()
                ->whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->withCount('children')
                ->get(),
            'featuredProducts' => Product::query()
                ->where('is_active', true)
                ->where('is_featured', true)
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
