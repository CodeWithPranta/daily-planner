<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Provides category-level insights for time spent, task completion, and trends.
 */
class CategoryInsightService
{
    /**
     * Get category insights for a user within a date range.
     *
     * @return array<int, array{id: int, name: string|null, color: string|null, icon: string|null, total_minutes: int, completed_count: int, total_count: int, completion: int, daily_target_minutes: int|null}>
     */
    public function insightsForUser(int $userId, Carbon $start, Carbon $end): array
    {
        $categories = Category::forUser($userId)
            ->with(['tasks' => function ($q) use ($start, $end, $userId): void {
                $q->where('user_id', $userId)
                    ->whereHas('dailyPlan', function ($q2) use ($start, $end): void {
                        $q2->whereBetween('date', [$start->startOfDay(), $end->endOfDay()]);
                    });
            }])
            ->get();

        $insights = [];
        foreach ($categories as $category) {
            $tasks = $category->tasks;
            $totalMinutes = (int) $tasks->sum('actual_minutes');
            $completedCount = $tasks->where('status', TaskStatus::Completed)->count();
            $totalCount = $tasks->count();

            $insights[] = [
                'id' => $category->id,
                'name' => $category->name,
                'color' => $category->color,
                'icon' => $category->icon,
                'total_minutes' => $totalMinutes,
                'completed_count' => $completedCount,
                'total_count' => $totalCount,
                'completion' => $totalCount > 0 ? (int) round($completedCount / $totalCount * 100) : 0,
                'daily_target_minutes' => $category->daily_target_minutes,
            ];
        }

        usort($insights, fn ($a, $b) => $b['total_minutes'] <=> $a['total_minutes']);

        return $insights;
    }

    /**
     * Get time distribution by category for a date range (for pie/doughnut chart).
     *
     * @return array<int, array{label: string, value: int, color: string|null}>
     */
    public function timeDistribution(int $userId, Carbon $start, Carbon $end): array
    {
        $results = DB::table('tasks')
            ->join('daily_plans', 'tasks.daily_plan_id', '=', 'daily_plans.id')
            ->leftJoin('categories', 'tasks.category_id', '=', 'categories.id')
            ->where('tasks.user_id', $userId)
            ->whereBetween('daily_plans.date', [$start->startOfDay(), $end->endOfDay()])
            ->where('tasks.actual_minutes', '>', 0)
            ->selectRaw('COALESCE(categories.id, 0) as cat_id, COALESCE(categories.name, ?) as cat_name, COALESCE(categories.color, ?) as cat_color, SUM(tasks.actual_minutes) as total', ['Uncategorized', '#71717a'])
            ->groupBy('cat_id', 'cat_name', 'cat_color')
            ->orderByDesc('total')
            ->get();

        return $results->map(fn ($r) => [
            'label' => $r->cat_name,
            'value' => (int) $r->total,
            'color' => $r->cat_color,
        ])->all();
    }

    /**
     * Get time trend by category for a date range (for stacked bar chart).
     *
     * @return array{labels: array<int, string>, datasets: array<int, array{label: string, color: string|null, data: array<int, int>}>}
     */
    public function weeklyTimeByCategory(int $userId, Carbon $start, Carbon $end): array
    {
        $results = DB::table('tasks')
            ->join('daily_plans', 'tasks.daily_plan_id', '=', 'daily_plans.id')
            ->leftJoin('categories', 'tasks.category_id', '=', 'categories.id')
            ->where('tasks.user_id', $userId)
            ->whereBetween('daily_plans.date', [$start->startOfDay(), $end->endOfDay()])
            ->where('tasks.actual_minutes', '>', 0)
            ->selectRaw('categories.id as cat_id, categories.name as cat_name, categories.color as cat_color, DATE(daily_plans.date) as date, SUM(tasks.actual_minutes) as total')
            ->groupBy('cat_id', 'cat_name', 'cat_color', 'date')
            ->orderBy('date')
            ->get();

        $labels = [];
        $date = $start->copy();
        $useDayNames = $start->diffInDays($end) < 7;
        while ($date <= $end) {
            $labels[] = $useDayNames
                ? $date->translatedFormat('D')
                : $date->translatedFormat('M j');
            $date->addDay();
        }

        $categories = $results->groupBy(fn ($r): int => (int) ($r->cat_id ?? 0));
        $datasets = [];

        foreach ($categories as $catId => $rows) {
            $first = $rows->first();
            $catName = $first->cat_name ?? 'Uncategorized';
            $catColor = $first->cat_color ?? '#71717a';

            $data = [];
            $date2 = $start->copy();
            while ($date2 <= $end) {
                $dateStr = $date2->toDateString();
                $row = $rows->first(fn ($r) => $r->date === $dateStr);
                $data[] = $row ? (int) $row->total : 0;
                $date2->addDay();
            }

            $datasets[] = [
                'label' => $catName,
                'color' => $catColor,
                'data' => $data,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }
}
