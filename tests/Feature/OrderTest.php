<?php

namespace Tests\Feature;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_orders(): void
    {
        $this->get('/orders')->assertRedirect('/login');
    }

    public function test_a_user_only_sees_their_own_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownOrder = SalesOrder::factory()->create(['user_id' => $user->id]);
        SalesOrder::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)
            ->get('/orders')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.id', $ownOrder->id)
            );
    }

    public function test_a_user_can_view_their_own_order_with_items(): void
    {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->create(['user_id' => $user->id]);
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id]);

        $this->actingAs($user)
            ->get("/orders/{$order->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->component('Shop/Orders/Show')
                ->where('order.id', $order->id)
                ->has('order.items', 1)
            );
    }

    public function test_a_user_cannot_view_another_users_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $order = SalesOrder::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($user)->get("/orders/{$order->id}")->assertForbidden();
    }
}
