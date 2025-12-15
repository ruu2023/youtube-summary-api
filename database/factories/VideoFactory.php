<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'video_id' => $this->faker->unique()->regexify('[a-zA-Z0-9_-]{11}'),
            'user_id' => 1,
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'category_id' => Category::factory()
        ];
    }
}
