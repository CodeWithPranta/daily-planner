<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $colors = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        $icons = ['folder', 'book-open', 'code-bracket', 'language', 'heart', 'briefcase'];

        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'icon' => fake()->randomElement($icons),
            'color' => fake()->randomElement($colors),
            'weekly_target_hours' => fake()->optional(0.5)->randomFloat(2, 5, 40),
            'daily_target_hours' => fake()->optional(0.5)->randomFloat(2, 1, 6),
            'is_active' => true,
        ];
    }

    public function archived(): static
    {
        return $this->state(['is_active' => false]);
    }
}
