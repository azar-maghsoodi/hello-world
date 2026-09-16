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
        [$name, $value] = fake()->unique()->randomElement([
            ['Color', 'Red'],
            ['Color', 'Blue'],
            ['Color', 'Green'],
            ['Color', 'Black'],
            ['Size', 'Small'],
            ['Size', 'Medium'],
            ['Size', 'Large'],
            ['Material', 'Cotton'],
            ['Material', 'Leather'],
        ]);

        return [
            'name' => $name,
            'value' => $value,
        ];
    }
}
