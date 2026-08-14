<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'planned_minutes' => fake()->randomElement([15, 30, 45, 60, 90, 120, 180]),
            'actual_minutes' => 0,
            'progress_percentage' => 0,
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'status' => TaskStatus::Pending,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => TaskStatus::Pending,
            'progress_percentage' => 0,
            'actual_minutes' => 0,
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => TaskStatus::Completed,
            'progress_percentage' => 100,
            'actual_minutes' => fake()->numberBetween(15, 180),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state([
            'status' => TaskStatus::InProgress,
            'progress_percentage' => fake()->numberBetween(25, 75),
            'actual_minutes' => fake()->numberBetween(15, 90),
        ]);
    }
}
