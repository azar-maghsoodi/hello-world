<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Profile;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAndOrderRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_has_one_profile_and_a_profile_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->profile->is($profile));
        $this->assertTrue($profile->user->is($user));
    }

    public function test_a_sales_order_belongs_to_a_user_and_a_user_has_many_sales_orders(): void
    {
        $user = User::factory()->create();
        $order = SalesOrder::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($order->user->is($user));
        $this->assertTrue($user->salesOrders->contains($order));
    }

    public function test_a_sales_order_has_many_items_and_each_item_belongs_to_a_product(): void
    {
        $order = SalesOrder::factory()->create();
        $product = Product::factory()->create();

        $item = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'unit_price' => $product->price,
            'quantity' => 3,
            'total' => $product->price * 3,
        ]);

        $this->assertTrue($order->items->contains($item));
        $this->assertTrue($item->salesOrder->is($order));
        $this->assertTrue($item->product->is($product));
        $this->assertTrue($product->salesOrderItems->contains($item));
    }

    public function test_an_order_item_keeps_its_product_snapshot_after_the_product_is_deleted(): void
    {
        $product = Product::factory()->create(['name' => 'Snapshot Widget', 'sku' => 'SNAP-1']);
        $item = SalesOrderItem::factory()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
        ]);

        $product->delete();
        $item->refresh();

        $this->assertNull($item->product_id);
        $this->assertSame('Snapshot Widget', $item->product_name);
        $this->assertSame('SNAP-1', $item->product_sku);
    }

    public function test_deleting_a_user_deletes_their_profile_and_orders(): void
    {
        $user = User::factory()->create();
        Profile::factory()->create(['user_id' => $user->id]);
        $order = SalesOrder::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseMissing('profiles', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sales_orders', ['id' => $order->id]);
    }
}
