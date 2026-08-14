<?php

namespace Database\Factories;

use App\Models\DailyScore;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyScore>
 */
class DailyScoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'date' => Carbon::now()->toDateString(),
            'score' => fake()->randomFloat(2, 0, 100),
            'total_tasks' => fake()->numberBetween(1, 10),
            'completed_tasks' => fake()->numberBetween(0, 8),
            'in_progress_tasks' => fake()->numberBetween(0, 3),
            'pending_tasks' => fake()->numberBetween(0, 3),
            'skipped_tasks' => fake()->numberBetween(0, 2),
            'partial_tasks' => fake()->numberBetween(0, 2),
            'total_planned_minutes' => fake()->numberBetween(60, 480),
            'total_actual_minutes' => fake()->numberBetween(0, 480),
            'category_breakdown' => null,
            'reflection' => null,
        ];
    }
}
