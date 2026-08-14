<?php

namespace App\Services;

use App\Models\DailyScore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Weekly summary of productivity scores.
 */
class WeeklySummary
{
    /**
     * @param  Collection<int, DailyScore>  $scores
     */
    public function __construct(
        public Carbon $weekStart,
        public Carbon $weekEnd,
        public Collection $scores,
        public int $totalCompleted,
        public int $totalTasks,
        public int $totalPlannedMinutes,
        public int $totalActualMinutes,
        public float $averageScore,
        public int $activeDayCount,
        public int $streakDayCount,
    ) {}

    public function completionPercent(): int
    {
        return $this->totalTasks > 0
            ? (int) round($this->totalCompleted / $this->totalTasks * 100)
            : 0;
    }

    public function timeUtilizationPercent(): int
    {
        return $this->totalPlannedMinutes > 0
            ? (int) min(100, round($this->totalActualMinutes / $this->totalPlannedMinutes * 100))
            : 0;
    }

    public function label(): string
    {
        return $this->weekStart->translatedFormat('M j').' - '.$this->weekEnd->translatedFormat('M j, Y');
    }
}
