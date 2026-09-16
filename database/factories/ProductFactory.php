<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $price = fake()->randomFloat(2, 5, 500);

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 999999),
            'sku' => strtoupper(Str::random(8)),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(),
            'price' => $price,
            'sale_price' => fake()->boolean(30) ? round($price * 0.8, 2) : null,
            'quantity' => fake()->numberBetween(0, 200),
            'weight' => fake()->randomFloat(2, 0.1, 20),
            'is_active' => true,
            'is_featured' => fake()->boolean(20),
        ];
    }
}
