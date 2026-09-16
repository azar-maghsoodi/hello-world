<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function show(Category $category): Response
    {
        $category->load(['parent', 'children' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order');
        }]);

        return Inertia::render('Shop/Category', [
            'category' => $category,
            'products' => $category->products()
                ->where('is_active', true)
                ->latest()
                ->paginate(12)
                ->withQueryString(),
        ]);
    }
}
