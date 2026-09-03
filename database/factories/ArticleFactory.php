<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'body_text' => fake()->paragraph(),
            'status' => 'published',
            'published_at' => now(),
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
