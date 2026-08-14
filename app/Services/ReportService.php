<?php

namespace App\Services;

use App\Models\DailyScore;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Generates weekly and monthly productivity reports.
 */
class ReportService
{
    /**
     * Get weekly summary for a user ending on the given date (defaults to today).
     */
    public function weeklySummary(int $userId, ?Carbon $referenceDate = null): WeeklySummary
    {
        $referenceDate ??= Carbon::today();

        $weekStart = $referenceDate->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);

        $scores = DailyScore::forUser($userId)
            ->betweenDates($weekStart, $weekEnd)
            ->orderBy('date')
            ->get();

        return $this->buildWeeklySummary($scores, $weekStart, $weekEnd);
    }

    /**
     * Get monthly summary for a user for the given month.
     */
    public function monthlySummary(int $userId, ?Carbon $referenceDate = null): MonthlySummary
    {
        $referenceDate ??= Carbon::today();

        $monthStart = $referenceDate->copy()->startOfMonth();
        $monthEnd = $referenceDate->copy()->endOfMonth();

        $scores = DailyScore::forUser($userId)
            ->betweenDates($monthStart, $monthEnd)
            ->orderBy('date')
            ->get();

        return $this->buildMonthlySummary($scores, $monthStart, $monthEnd);
    }

    /**
     * Get daily score trend data for a date range (for charting).
     *
     * @param  Collection<int, DailyScore>  $scores
     * @return array<int, array{date: string, score: float, completed: int}>
     */
    public function trendData(Collection $scores): array
    {
        return $scores->map(fn (DailyScore $s) => [
            'date' => $s->date->toDateString(),
            'score' => round($s->score, 1),
            'completed' => $s->completed_tasks,
        ])->values()->all();
    }

    /**
     * @param  Collection<int, DailyScore>  $scores
     */
    private function buildWeeklySummary(Collection $scores, Carbon $weekStart, Carbon $weekEnd): WeeklySummary
    {
        $activeDays = $scores->filter(fn (DailyScore $s) => $s->total_tasks > 0);
        $streakDays = $scores->filter(fn (DailyScore $s) => $s->countsAsStreakDay());

        $avgScore = $activeDays->isNotEmpty()
            ? round($activeDays->avg('score'), 1)
            : 0.0;

        return new WeeklySummary(
            weekStart: $weekStart,
            weekEnd: $weekEnd,
            scores: $scores,
            totalCompleted: (int) $scores->sum('completed_tasks'),
            totalTasks: (int) $scores->sum('total_tasks'),
            totalPlannedMinutes: (int) $scores->sum('total_planned_minutes'),
            totalActualMinutes: (int) $scores->sum('total_actual_minutes'),
            averageScore: $avgScore,
            activeDayCount: $activeDays->count(),
            streakDayCount: $streakDays->count(),
        );
    }

    /**
     * @param  Collection<int, DailyScore>  $scores
     */
    private function buildMonthlySummary(Collection $scores, Carbon $monthStart, Carbon $monthEnd): MonthlySummary
    {
        $activeDays = $scores->filter(fn (DailyScore $s) => $s->total_tasks > 0);
        $streakDays = $scores->filter(fn (DailyScore $s) => $s->countsAsStreakDay());

        $avgScore = $activeDays->isNotEmpty()
            ? round($activeDays->avg('score'), 1)
            : 0.0;

        $bestDay = $activeDays->isNotEmpty()
            ? $activeDays->sortByDesc('score')->first()
            : null;

        $totalDays = (int) ($monthStart->diffInDays($monthEnd) + 1);

        return new MonthlySummary(
            monthStart: $monthStart,
            monthEnd: $monthEnd,
            scores: $scores,
            totalCompleted: (int) $scores->sum('completed_tasks'),
            totalTasks: (int) $scores->sum('total_tasks'),
            totalPlannedMinutes: (int) $scores->sum('total_planned_minutes'),
            totalActualMinutes: (int) $scores->sum('total_actual_minutes'),
            averageScore: $avgScore,
            activeDayCount: $activeDays->count(),
            streakDayCount: $streakDays->count(),
            totalDaysInMonth: $totalDays,
            bestDay: $bestDay,
        );
    }
}
