<?php

namespace Database\Factories;

use App\Enums\TimerStatus;
use App\Models\TimerSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimerSession>
 */
class TimerSessionFactory extends Factory
{
    public function definition(): array
    {
        $started = Carbon::now()->subMinutes(fake()->numberBetween(5, 120));

        return [
            'started_at' => $started,
            'paused_at' => null,
            'resumed_at' => null,
            'ended_at' => null,
            'total_seconds' => 0,
            'status' => TimerStatus::Running,
        ];
    }

    public function stopped(int $seconds = 3600): static
    {
        $started = Carbon::now()->subSeconds($seconds);

        return $this->state([
            'started_at' => $started,
            'ended_at' => Carbon::now(),
            'total_seconds' => $seconds,
            'status' => TimerStatus::Stopped,
        ]);
    }
}
