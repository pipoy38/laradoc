<?php

namespace Database\Factories;

use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    protected $model = MenuItem::class;

    public function definition(): array
    {
        return [
            'label' => fake()->words(2, true),
            'url' => 'https://example.com',
            'new_tab' => false,
            'sort_order' => 0,
        ];
    }
}
