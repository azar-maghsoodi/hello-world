<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_add_a_product_to_the_cart(): void
    {
        $product = Product::factory()->create(['quantity' => 10, 'price' => 25, 'sale_price' => null]);

        $this->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page
            ->component('Shop/Cart')
            ->has('items', 1)
            ->where('items.0.quantity', 2)
            ->where('subtotal', 50)
        );
    }

    public function test_adding_more_than_available_stock_is_capped(): void
    {
        $product = Product::factory()->create(['quantity' => 3]);

        $this->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.quantity', 3)
        );
    }

    public function test_adding_an_out_of_stock_product_fails(): void
    {
        $product = Product::factory()->create(['quantity' => 0]);

        $response = $this->from('/products/'.$product->slug)->post('/cart', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_the_cart_quantity_can_be_updated(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 1]);

        $this->patch("/cart/{$product->id}", ['quantity' => 4])->assertRedirect();

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page
            ->where('items.0.quantity', 4)
        );
    }

    public function test_setting_quantity_to_zero_removes_the_item(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $this->patch("/cart/{$product->id}", ['quantity' => 0]);

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_an_item_can_be_removed_from_the_cart(): void
    {
        $product = Product::factory()->create(['quantity' => 10]);
        $this->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $this->delete("/cart/{$product->id}")->assertRedirect();

        $this->get('/cart')->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }
}
