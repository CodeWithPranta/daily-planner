<?php

namespace Database\Factories;

use App\Models\DailyPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyPlan>
 */
class DailyPlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date' => fake()->date(),
            'status' => 'draft',
        ];
    }

    public function planned(): static
    {
        return $this->state(['status' => 'planned']);
    }

    public function reviewed(): static
    {
        return $this->state(['status' => 'reviewed']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
