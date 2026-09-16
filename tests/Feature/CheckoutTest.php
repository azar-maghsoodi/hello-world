<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_to_checkout(): void
    {
        $this->get('/checkout')->assertRedirect('/login');
    }

    public function test_checkout_redirects_to_cart_when_empty(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/checkout')->assertRedirect('/cart');
    }

    public function test_a_user_can_place_an_order_from_their_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'sale_price' => null, 'quantity' => 10]);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->post('/checkout', [
            'shipping_address' => "123 Main St\nSpringfield",
        ]);

        $order = SalesOrder::where('user_id', $user->id)->firstOrFail();
        $response->assertRedirect("/orders/{$order->id}");

        $this->assertSame(1, $order->items()->count());
        $item = $order->items()->first();
        $this->assertSame(2, $item->quantity);
        $this->assertEquals(100, $item->total);
        $this->assertEquals(100, $order->subtotal);
        $this->assertEquals(8, $order->tax);
        $this->assertEquals(108, $order->total);

        $this->assertSame(8, $product->fresh()->quantity);
    }

    public function test_placing_an_order_clears_the_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($user)->post('/checkout', ['shipping_address' => 'Somewhere']);

        $this->actingAs($user)->get('/cart')->assertInertia(fn (Assert $page) => $page->has('items', 0));
    }

    public function test_checkout_fails_if_stock_dropped_below_cart_quantity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 5]);

        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 5]);

        // simulate another sale reducing stock after it was added to the cart
        $product->update(['quantity' => 2]);

        $response = $this->actingAs($user)->post('/checkout', ['shipping_address' => 'Somewhere']);

        $response->assertRedirect('/cart');
        $response->assertSessionHasErrors('quantity');
        $this->assertSame(0, SalesOrder::where('user_id', $user->id)->count());
        $this->assertSame(2, $product->fresh()->quantity);
    }

    public function test_shipping_address_is_required(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['quantity' => 10]);
        $this->actingAs($user)->post('/cart', ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->post('/checkout', []);

        $response->assertSessionHasErrors('shipping_address');
    }
}
