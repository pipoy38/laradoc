<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'disk_path' => 'media/2026/09/'.Str::random(8).'.png',
            'original_name' => 'x.png',
            'mime' => 'image/png',
            'size' => 1000,
            'width' => 10,
            'height' => 10,
        ];
    }
}
