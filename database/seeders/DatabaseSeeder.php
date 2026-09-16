<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        StoreSetting::create([
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'tax_rate' => 0,
            'shipping_cost' => 0,
        ]);

        collect([
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'sort_order' => 0],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false, 'sort_order' => 1],
            ['code' => 'de', 'name' => 'German', 'native_name' => 'Deutsch', 'is_default' => false, 'sort_order' => 2],
            ['code' => 'es', 'name' => 'Spanish', 'native_name' => 'Español', 'is_default' => false, 'sort_order' => 3],
            ['code' => 'it', 'name' => 'Italian', 'native_name' => 'Italiano', 'is_default' => false, 'sort_order' => 4],
        ])->each(fn (array $language) => Language::create([...$language, 'is_active' => true]));

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $admin->profile()->create(['phone' => '555-0100', 'city' => 'Metropolis']);

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);
        $customer->profile()->create(['phone' => '555-0199', 'city' => 'Gotham']);

        $attributes = collect([
            ['name' => 'Color', 'value' => 'Red'],
            ['name' => 'Color', 'value' => 'Blue'],
            ['name' => 'Color', 'value' => 'Black'],
            ['name' => 'Size', 'value' => 'Small'],
            ['name' => 'Size', 'value' => 'Medium'],
            ['name' => 'Size', 'value' => 'Large'],
            ['name' => 'Material', 'value' => 'Cotton'],
        ])->map(fn (array $attribute) => ProductAttribute::create($attribute));

        $categoryTree = [
            'Electronics' => ['Phones', 'Laptops', 'Headphones'],
            'Clothing' => ['Men', 'Women', 'Kids'],
            'Home & Garden' => ['Furniture', 'Kitchen'],
        ];

        $allProducts = collect();

        foreach ($categoryTree as $parentName => $children) {
            $parent = Category::factory()->create(['name' => $parentName]);

            foreach ($children as $childName) {
                $child = Category::factory()->create(['name' => $childName, 'parent_id' => $parent->id]);

                Product::factory()
                    ->count(4)
                    ->create()
                    ->each(function (Product $product) use ($child, $attributes, $allProducts) {
                        $child->products()->attach($product->id);
                        $product->attributes()->attach(
                            $attributes->random(random_int(1, 3))->pluck('id')
                        );
                        $allProducts->push($product);
                    });
            }
        }

        collect(range(1, 3))->each(function (int $i) use ($customer, $allProducts) {
            $lineItems = $allProducts->random(random_int(1, 3));

            $order = SalesOrder::factory()->create([
                'user_id' => $customer->id,
                'order_number' => 'ORD-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'status' => SalesOrder::STATUS_PENDING,
            ]);

            $subtotal = 0;

            foreach ($lineItems as $product) {
                $quantity = random_int(1, 3);
                $total = $product->price * $quantity;
                $subtotal += $total;

                SalesOrderItem::create([
                    'sales_order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total' => $total,
                ]);
            }

            $taxRate = (float) StoreSetting::current()->tax_rate;
            $tax = round($subtotal * $taxRate, 2);

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
            ]);
        });
    }
}
