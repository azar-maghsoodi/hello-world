<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_lists_top_level_categories(): void
    {
        $parent = Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['parent_id' => $parent->id]);

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Home')
            ->has('categories', 1)
            ->where('categories.0.id', $parent->id)
        );
    }

    public function test_a_category_page_shows_its_products_and_subcategories(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);
        $product = Product::factory()->create(['is_active' => true]);
        $child->products()->attach($product);

        $this->get("/categories/{$child->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Category')
                ->where('category.id', $child->id)
                ->where('category.parent.id', $parent->id)
                ->has('products.data', 1)
            );
    }

    public function test_a_product_page_shows_its_categories_and_attributes(): void
    {
        $product = Product::factory()->create();

        $this->get("/products/{$product->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Product')
                ->where('product.id', $product->id)
            );
    }
}
