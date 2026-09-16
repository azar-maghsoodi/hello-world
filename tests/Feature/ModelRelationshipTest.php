<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_category_can_have_a_parent_and_children(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($parent->children->contains($child));
    }

    public function test_categories_and_products_belong_to_many_of_each_other(): void
    {
        $category = Category::factory()->create();
        $products = Product::factory()->count(2)->create();

        $category->products()->attach($products->pluck('id'));

        $this->assertCount(2, $category->products);
        $this->assertTrue($products->first()->categories->contains($category));
    }

    public function test_products_and_attributes_belong_to_many_of_each_other(): void
    {
        $product = Product::factory()->create();
        $attributes = ProductAttribute::factory()->count(3)->create();

        $product->attributes()->attach($attributes->pluck('id'));

        $this->assertCount(3, $product->attributes);
        $this->assertTrue($attributes->first()->products->contains($product));
    }
}
