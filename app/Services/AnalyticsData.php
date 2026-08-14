<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Aggregated analytics data for the analytics page.
 */
class AnalyticsData
{
    /**
     * @param  array<int, array{date: string, label: string, score: float, completed: int}>  $scoreTrend
     * @param  array<int, array{label: string, value: int, color: string|null}>  $categoryDistribution
     * @param  array{labels: array<int, string>, datasets: array<int, array{label: string, color: string|null, data: array<int, int>}>}  $weeklyTimeByCategory
     * @param  array<int, array{id: int, title: string, progress: int, target_date: Carbon|null, category_name: string|null, category_color: string|null}>  $goals
     */
    public function __construct(
        public string $range,
        public array $scoreTrend,
        public float $averageScore,
        public int $totalCompleted,
        public int $totalTasks,
        public int $totalActualMinutes,
        public int $totalPlannedMinutes,
        public int $completionPercent,
        public int $currentStreak,
        public int $longestStreak,
        public array $categoryDistribution,
        public array $weeklyTimeByCategory,
        public array $goals,
        public int $activeDayCount,
        public int $totalDays,
    ) {}

    public function timeUtilizationPercent(): int
    {
        return $this->totalPlannedMinutes > 0
            ? (int) min(100, round($this->totalActualMinutes / $this->totalPlannedMinutes * 100))
            : 0;
    }

    public function consistencyPercent(): int
    {
        return $this->totalDays > 0
            ? (int) round($this->activeDayCount / $this->totalDays * 100)
            : 0;
    }

    public function totalActualHours(): float
    {
        return round($this->totalActualMinutes / 60, 1);
    }
}
