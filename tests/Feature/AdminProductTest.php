<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_an_admin_can_create_a_product_with_categories_and_attributes(): void
    {
        $categories = Category::factory()->count(2)->create();
        $attributes = ProductAttribute::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Wireless Mouse',
            'sku' => 'WM-100',
            'price' => 29.99,
            'quantity' => 50,
            'is_active' => true,
            'categories' => $categories->pluck('id')->all(),
            'attributes' => $attributes->pluck('id')->all(),
        ]);

        $response->assertRedirect('/admin/products');

        $product = Product::where('sku', 'WM-100')->firstOrFail();
        $this->assertSame('wireless-mouse', $product->slug);
        $this->assertCount(2, $product->categories);
        $this->assertCount(2, $product->attributes);
    }

    public function test_sale_price_must_be_less_than_price(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/products', [
            'name' => 'Wireless Mouse',
            'sku' => 'WM-100',
            'price' => 10,
            'sale_price' => 15,
            'quantity' => 5,
        ]);

        $response->assertSessionHasErrors('sale_price');
    }

    public function test_an_admin_can_update_a_products_categories(): void
    {
        $product = Product::factory()->create();
        $oldCategory = Category::factory()->create();
        $newCategory = Category::factory()->create();
        $product->categories()->attach($oldCategory);

        $this->actingAs($this->admin)->put("/admin/products/{$product->id}", [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $product->quantity,
            'categories' => [$newCategory->id],
        ])->assertRedirect('/admin/products');

        $product->refresh();
        $this->assertTrue($product->categories->contains($newCategory));
        $this->assertFalse($product->categories->contains($oldCategory));
    }

    public function test_an_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/products/{$product->id}")
            ->assertRedirect('/admin/products');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
