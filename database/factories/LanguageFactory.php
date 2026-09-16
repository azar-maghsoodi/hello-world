<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$code, $name, $native] = fake()->unique()->randomElement([
            ['en', 'English', 'English'],
            ['fr', 'French', 'Français'],
            ['de', 'German', 'Deutsch'],
            ['es', 'Spanish', 'Español'],
            ['it', 'Italian', 'Italiano'],
            ['nl', 'Dutch', 'Nederlands'],
            ['pt', 'Portuguese', 'Português'],
        ]);

        return [
            'code' => $code,
            'name' => $name,
            'native_name' => $native,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
