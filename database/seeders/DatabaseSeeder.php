<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
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
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);

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

        foreach ($categoryTree as $parentName => $children) {
            $parent = Category::factory()->create(['name' => $parentName]);

            foreach ($children as $childName) {
                $child = Category::factory()->create(['name' => $childName, 'parent_id' => $parent->id]);

                Product::factory()
                    ->count(4)
                    ->create()
                    ->each(function (Product $product) use ($child, $attributes) {
                        $child->products()->attach($product->id);
                        $product->attributes()->attach(
                            $attributes->random(random_int(1, 3))->pluck('id')
                        );
                    });
            }
        }
    }
}
