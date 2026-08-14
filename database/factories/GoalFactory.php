<?php

namespace Database\Factories;

use App\Enums\GoalStatus;
use App\Models\Goal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        $hasValue = fake()->boolean(70);
        $hasDate = fake()->boolean(60);

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'target_date' => $hasDate ? fake()->dateTimeBetween('+1 month', '+1 year')->format('Y-m-d') : null,
            'target_value' => $hasValue ? fake()->randomFloat(2, 10, 1000) : null,
            'current_value' => $hasValue ? fake()->randomFloat(2, 0, 800) : null,
            'status' => fake()->randomElement(GoalStatus::cases()),
        ];
    }
}
