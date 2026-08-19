<?php

namespace App\Services;

use App\Models\DailyScore;
use App\Models\Goal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Aggregates all analytics data for the analytics page.
 */
class AnalyticsService
{
    public function __construct(
        private StreakService $streakService,
        private CategoryInsightService $categoryInsightService,
        private DailyProductivityScoreService $scoreService,
    ) {}

    /**
     * Get comprehensive analytics for a user within a date range.
     */
    public function getAnalytics(int $userId, string $range): AnalyticsData
    {
        [$start, $end] = $this->resolveRange($range);

        // Auto-populate any missing daily score rows from existing tasks so the
        // page reflects real activity even without an end-of-day review.
        $this->scoreService->ensureRangePopulated($userId, $start, $end);

        // Daily scores for trend
        $scores = DailyScore::forUser($userId)
            ->betweenDates($start, $end)
            ->orderBy('date')
            ->get();

        // Score trend for chart
        $scoreTrend = $this->buildScoreTrend($scores, $start, $end);

        // Summary stats
        $avgScore = $scores->isNotEmpty() ? round($scores->avg('score'), 1) : 0.0;
        $totalCompleted = (int) $scores->sum('completed_tasks');
        $totalTasks = (int) $scores->sum('total_tasks');
        $totalActualMinutes = (int) $scores->sum('total_actual_minutes');
        $totalPlannedMinutes = (int) $scores->sum('total_planned_minutes');

        // Streaks
        $currentStreak = $this->streakService->currentStreak($userId);
        $longestStreak = $this->streakService->longestStreak($userId);

        // Category time distribution
        $categoryDistribution = $this->categoryInsightService->timeDistribution($userId, $start, $end);

        // Time by category for the selected range
        $weeklyTimeByCategory = $this->categoryInsightService->weeklyTimeByCategory($userId, $start, $end);

        // Active goals
        $goals = Goal::forUser($userId)
            ->active()
            ->with('category')
            ->orderBy('target_date')
            ->limit(5)
            ->get()
            ->map(fn (Goal $g) => [
                'id' => $g->id,
                'title' => $g->title,
                'progress' => $g->progress_percentage,
                'target_date' => $g->target_date,
                'category_name' => $g->category?->name,
                'category_color' => $g->category?->color,
            ]);

        return new AnalyticsData(
            range: $range,
            scoreTrend: $scoreTrend,
            averageScore: $avgScore,
            totalCompleted: $totalCompleted,
            totalTasks: $totalTasks,
            totalActualMinutes: $totalActualMinutes,
            totalPlannedMinutes: $totalPlannedMinutes,
            completionPercent: $totalTasks > 0 ? (int) round($totalCompleted / $totalTasks * 100) : 0,
            currentStreak: $currentStreak,
            longestStreak: $longestStreak,
            categoryDistribution: $categoryDistribution,
            weeklyTimeByCategory: $weeklyTimeByCategory,
            goals: $goals->all(),
            activeDayCount: $scores->filter(fn (DailyScore $s) => $s->total_tasks > 0)->count(),
            totalDays: (int) ($start->diffInDays($end) + 1),
        );
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function resolveRange(string $range): array
    {
        $today = Carbon::today();

        return match ($range) {
            'week' => [$today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
            '30days' => [$today->copy()->subDays(29), $today],
            default => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
        };
    }

    /**
     * @param  Collection<int, DailyScore>  $scores
     * @return array<int, array{date: string, label: string, score: float, completed: int}>
     */
    private function buildScoreTrend(Collection $scores, Carbon $start, Carbon $end): array
    {
        $scoreMap = $scores->keyBy(fn (DailyScore $s) => $s->date->toDateString());

        $trend = [];
        $date = $start->copy();
        while ($date <= $end) {
            $score = $scoreMap->get($date->toDateString());

            $trend[] = [
                'date' => $date->toDateString(),
                'label' => $date->translatedFormat('M j'),
                'score' => $score !== null ? round($score->score, 1) : 0.0,
                'completed' => $score !== null ? $score->completed_tasks : 0,
            ];
            $date->addDay();
        }

        return $trend;
    }
}
