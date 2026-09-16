<?php

namespace Tests\Feature;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminSalesOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_customers_cannot_access_admin_orders(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $order = SalesOrder::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($customer)->get("/admin/orders/{$order->id}")->assertForbidden();
    }

    public function test_an_admin_sees_every_users_orders(): void
    {
        $order1 = SalesOrder::factory()->create();
        $order2 = SalesOrder::factory()->create();

        $this->actingAs($this->admin)
            ->get('/admin/orders')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 2)
            );
    }

    public function test_an_admin_can_filter_orders_by_status(): void
    {
        SalesOrder::factory()->create(['status' => SalesOrder::STATUS_PENDING]);
        SalesOrder::factory()->create(['status' => SalesOrder::STATUS_COMPLETED]);

        $this->actingAs($this->admin)
            ->get('/admin/orders?status=completed')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Orders/Index')
                ->has('orders.data', 1)
                ->where('orders.data.0.status', SalesOrder::STATUS_COMPLETED)
            );
    }

    public function test_an_admin_can_update_an_orders_status(): void
    {
        $order = SalesOrder::factory()->create(['status' => SalesOrder::STATUS_PENDING]);

        $this->actingAs($this->admin)
            ->put("/admin/orders/{$order->id}", ['status' => SalesOrder::STATUS_PROCESSING])
            ->assertRedirect("/admin/orders/{$order->id}");

        $this->assertSame(SalesOrder::STATUS_PROCESSING, $order->fresh()->status);
    }
}
