<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $n = fake()->unique()->words(2, true);

        return [
            'name' => $n,
            'slug' => Str::slug($n),
            'sort_order' => 0,
        ];
    }
}
