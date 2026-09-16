<?php

namespace Database\Factories;

use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.fake()->unique()->numerify('######'),
            'user_id' => User::factory(),
            'status' => SalesOrder::STATUS_PENDING,
            'subtotal' => 0,
            'tax' => 0,
            'shipping_cost' => 0,
            'total' => 0,
        ];
    }
}
