<?php

namespace Database\Factories;

use App\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Color', 'Size', 'Material']),
            'value' => fake()->randomElement(['Red', 'Blue', 'Green', 'Small', 'Medium', 'Large', 'Cotton', 'Leather']),
        ];
    }
}
