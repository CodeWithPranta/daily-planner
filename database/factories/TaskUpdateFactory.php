<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\TaskUpdate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskUpdate>
 */
class TaskUpdateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'progress_percentage' => fake()->numberBetween(10, 100),
            'worked_minutes' => fake()->numberBetween(15, 120),
            'notes' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
        ];
    }
}
