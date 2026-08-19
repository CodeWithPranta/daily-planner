<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\DailyPlan;
use App\Models\DailyScore;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Result of a daily productivity score calculation.
 */
class DailyScoreResult
{
    public function __construct(
        public float $score,
        public int $completedCount,
        public int $inProgressCount,
        public int $pendingCount,
        public int $skippedCount,
        public int $partialCount,
        public int $totalPlannedMinutes,
        public int $totalActualMinutes,
        public int $totalCount,
        /** @var array<int, array{id: int, name: string|null, color: string|null, planned: int, actual: int, completion: int}> */
        public array $categoryBreakdown,
    ) {}

    public function scorePercent(): int
    {
        return (int) round($this->score);
    }

    public function completionPercent(): int
    {
        return $this->totalCount > 0
            ? (int) round($this->completedCount / $this->totalCount * 100)
            : 0;
    }
}

/**
 * Calculates a daily productivity score from 0 to 100 based on task completion,
 * progress, planned vs actual time, and task priority weighting.
 */
class DailyProductivityScoreService
{
    /**
     * The percentage of the final score that time performance contributes.
     * 0 means time performance is ignored; 20 means 80% task achievement / 20% time.
     */
    private const TIME_WEIGHT_PERCENT = 15;

    /**
     * Calculate the daily productivity score for a daily plan.
     */
    public function calculateForPlan(DailyPlan $plan): DailyScoreResult
    {
        $tasks = $plan->tasks()->with('category')->get();

        return $this->calculate($tasks);
    }

    /**
     * Calculate the daily productivity score for a user on a given date.
     */
    public function calculateForUser(int $userId, Carbon $date): DailyScoreResult
    {
        $tasks = Task::forUser($userId)
            ->forDate($date)
            ->with('category')
            ->get();

        return $this->calculate($tasks);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     */
    public function calculate(Collection $tasks): DailyScoreResult
    {
        $completedCount = $tasks->where('status', TaskStatus::Completed)->count();
        $inProgressCount = $tasks->where('status', TaskStatus::InProgress)->count();
        $pendingCount = $tasks->where('status', TaskStatus::Pending)->count();
        $skippedCount = $tasks->where('status', TaskStatus::Skipped)->count();
        $partialCount = $tasks->where('status', TaskStatus::PartiallyCompleted)->count();

        $totalPlanned = (int) $tasks->sum('planned_minutes');
        $totalActual = (int) $tasks->sum('actual_minutes');

        $achievementScore = $this->calculateAchievementScore($tasks);
        $timeScore = $this->calculateTimeScore($totalPlanned, $totalActual);

        $timeWeight = self::TIME_WEIGHT_PERCENT / 100;
        $achievementWeight = 1 - $timeWeight;

        $score = $tasks->isEmpty()
            ? 0
            : ($achievementScore * $achievementWeight + $timeScore * $timeWeight);

        $score = min(100, max(0, $score));

        return new DailyScoreResult(
            score: $score,
            completedCount: $completedCount,
            inProgressCount: $inProgressCount,
            pendingCount: $pendingCount,
            skippedCount: $skippedCount,
            partialCount: $partialCount,
            totalPlannedMinutes: $totalPlanned,
            totalActualMinutes: $totalActual,
            totalCount: $tasks->count(),
            categoryBreakdown: $this->buildCategoryBreakdown($tasks),
        );
    }

    /**
     * Weighted achievement score: Sum(Weight × Achievement) / Sum(Weight) × 100.
     *
     * @param  Collection<int, Task>  $tasks
     */
    private function calculateAchievementScore(Collection $tasks): float
    {
        $totalWeight = 0;
        $totalAchievement = 0;

        foreach ($tasks as $task) {
            $planned = max(1, $task->planned_minutes);
            $weight = $task->priority->weight() * $planned;

            $achievement = match ($task->status) {
                TaskStatus::Completed => 1.0,
                TaskStatus::PartiallyCompleted => $task->progress_percentage / 100,
                TaskStatus::InProgress => $task->progress_percentage / 100,
                TaskStatus::Skipped => 0.0,
                TaskStatus::Pending => 0.0,
            };

            $totalWeight += $weight;
            $totalAchievement += $weight * $achievement;
        }

        return $totalWeight > 0 ? ($totalAchievement / $totalWeight) * 100 : 0;
    }

    /**
     * Time performance: actual minutes vs planned minutes (capped at 100%).
     */
    private function calculateTimeScore(int $planned, int $actual): float
    {
        if ($planned <= 0) {
            return 0;
        }

        return min(100, ($actual / $planned) * 100);
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @return array<int, array{id: int, name: string|null, color: string|null, planned: int, actual: int, completion: int}>
     */
    private function buildCategoryBreakdown(Collection $tasks): array
    {
        $grouped = $tasks->groupBy(fn (Task $t, int $_): int => (int) $t->category_id);

        $breakdown = [];
        foreach ($grouped as $categoryId => $categoryTasks) {
            $category = $categoryTasks->first()?->category;
            $planned = (int) $categoryTasks->sum('planned_minutes');
            $actual = (int) $categoryTasks->sum('actual_minutes');
            $completion = $planned > 0 ? (int) min(100, round($actual / $planned * 100)) : 0;

            $breakdown[] = [
                'id' => (int) $categoryId,
                'name' => $category?->name,
                'color' => $category?->color,
                'planned' => $planned,
                'actual' => $actual,
                'completion' => $completion,
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['actual'] <=> $a['actual']);

        return $breakdown;
    }

    /**
     * Calculate the score for a user on a given date and persist it as a DailyScore record.
     */
    public function storeForUser(int $userId, Carbon $date, ?string $reflection = null): DailyScore
    {
        $attributes = $this->scoreAttributes($this->calculateForUser($userId, $date));
        $attributes['reflection'] = $reflection;

        return $this->persist($userId, $date, $attributes);
    }

    /**
     * Recalculate and persist the daily score for a user/date without touching
     * the reflection text. Use this whenever a task changes so analytics and
     * streaks stay in sync without requiring an end-of-day review.
     */
    public function refreshForUser(int $userId, Carbon $date): DailyScore
    {
        return $this->persist($userId, $date, $this->scoreAttributes($this->calculateForUser($userId, $date)));
    }

    /**
     * Refresh the daily score for the plan date that the given task belongs to.
     * No-op when the task is not attached to a daily plan.
     */
    public function refreshForTask(Task $task): ?DailyScore
    {
        if (! $task->daily_plan_id) {
            return null;
        }

        $plan = $task->relationLoaded('dailyPlan') ? $task->dailyPlan : $task->dailyPlan()->first();

        return $plan ? $this->refreshForUser((int) $task->user_id, Carbon::parse($plan->date)) : null;
    }

    /**
     * Ensure a daily score row exists for every day in [start, end] that has
     * planned tasks. Missing days are calculated from current task data and
     * persisted (existing rows, including review reflections, are left alone).
     * This makes analytics/streaks reflect real activity without requiring an
     * end-of-day review or a manual backfill command.
     */
    public function ensureRangePopulated(int $userId, Carbon $start, Carbon $end): void
    {
        $planDates = DailyPlan::forUser($userId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereHas('tasks')
            ->get()
            ->map(fn (DailyPlan $plan) => $plan->date->toDateString())
            ->all();

        if ($planDates === []) {
            return;
        }

        $existingKeys = array_flip(
            DailyScore::forUser($userId)
                ->betweenDates($start, $end)
                ->get()
                ->map(fn (DailyScore $score) => $score->date->toDateString())
                ->all()
        );

        foreach ($planDates as $date) {
            if (! isset($existingKeys[$date])) {
                $this->refreshForUser($userId, Carbon::parse($date));
            }
        }
    }

    /**
     * Get a stored DailyScore for a user on a given date, or calculate and store it.
     */
    public function getOrCreateForUser(int $userId, Carbon $date): DailyScore
    {
        $score = DailyScore::forUser($userId)->forDate($date)->first();

        return $score ?? $this->storeForUser($userId, $date);
    }

    /**
     * @return array<string, float|int|string>
     */
    private function scoreAttributes(DailyScoreResult $result): array
    {
        return [
            'score' => $result->score,
            'total_tasks' => $result->totalCount,
            'completed_tasks' => $result->completedCount,
            'in_progress_tasks' => $result->inProgressCount,
            'pending_tasks' => $result->pendingCount,
            'skipped_tasks' => $result->skippedCount,
            'partial_tasks' => $result->partialCount,
            'total_planned_minutes' => $result->totalPlannedMinutes,
            'total_actual_minutes' => $result->totalActualMinutes,
            'category_breakdown' => json_encode($result->categoryBreakdown) ?: '[]',
        ];
    }

    /**
     * @param  array<string, float|int|string>  $attributes
     */
    private function persist(int $userId, Carbon $date, array $attributes): DailyScore
    {
        $match = ['user_id' => $userId, 'date' => $date->toDateString()];

        try {
            return DailyScore::updateOrCreate($match, $attributes);
        } catch (UniqueConstraintViolationException) {
            // A concurrent request inserted the row between our find and
            // insert. Re-fetch it and update in place instead.
            $score = DailyScore::forUser($userId)->forDate($date)->firstOrFail();
            $score->forceFill($attributes)->save();

            return $score->refresh();
        }
    }
}
