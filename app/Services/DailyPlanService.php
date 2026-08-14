<?php

namespace App\Services;

use App\Enums\DailyPlanStatus;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Handles daily plan lifecycle: get-or-create, duplicate, copy, marking planned.
 */
class DailyPlanService
{
    /**
     * Get or create a daily plan for a user on a given date.
     */
    public function getOrCreateFor(User|int $user, Carbon $date): DailyPlan
    {
        $userId = is_int($user) ? $user : $user->id;

        return DailyPlan::query()
            ->where('user_id', $userId)
            ->whereDate('date', $date->toDateString())
            ->first() ?? $this->create($userId, $date);
    }

    public function create(int $userId, Carbon $date): DailyPlan
    {
        return DailyPlan::firstOrCreate(
            ['user_id' => $userId, 'date' => $date->toDateString()],
            ['status' => DailyPlanStatus::Draft],
        );
    }

    /**
     * Duplicate all tasks from an origin plan into a target plan, replacing target's tasks.
     * Returns the number of tasks duplicated.
     */
    public function duplicateTasks(DailyPlan $target, DailyPlan $origin): int
    {
        abort_unless($target->user_id === $origin->user_id, 403);

        $originTasks = $origin->tasks()->orderBy('order_column')->get();
        $count = 0;

        DB::transaction(function () use ($target, $originTasks, &$count) {
            foreach ($originTasks as $task) {
                $this->cloneTaskIntoPlan($target, $task);
                $count++;
            }
        });

        return $count;
    }

    public function cloneTaskIntoPlan(DailyPlan $plan, Task $origin): Task
    {
        return Task::create([
            'user_id' => $plan->user_id,
            'daily_plan_id' => $plan->id,
            'category_id' => $origin->category_id,
            'goal_id' => $origin->goal_id,
            'title' => $origin->title,
            'description' => $origin->description,
            'planned_minutes' => $origin->planned_minutes,
            'priority' => $origin->priority->value,
            'order_column' => $this->nextOrder($plan),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addTask(DailyPlan $plan, array $data): Task
    {
        return DB::transaction(function () use ($plan, $data) {
            return Task::create(array_merge([
                'user_id' => $plan->user_id,
                'daily_plan_id' => $plan->id,
                'planned_minutes' => 0,
                'actual_minutes' => 0,
                'progress_percentage' => 0,
                'priority' => 'medium',
                'status' => 'pending',
                'order_column' => $this->nextOrder($plan),
            ], $data));
        });
    }

    public function markPlanned(DailyPlan $plan): DailyPlan
    {
        $plan->update(['status' => DailyPlanStatus::Planned]);

        return $plan;
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorderTasks(DailyPlan $plan, array $orderedIds): void
    {
        DB::transaction(function () use ($plan, $orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Task::where('id', $id)
                    ->where('daily_plan_id', $plan->id)
                    ->where('user_id', $plan->user_id)
                    ->update(['order_column' => ($index + 1) * 10]);
            }
        });
    }

    public function nextOrder(DailyPlan $plan): int
    {
        return (int) (Task::where('daily_plan_id', $plan->id)->max('order_column') ?? 0) + 10;
    }

    /**
     * List previous plans for the user (for the copy-a-previous-day selector).
     *
     * @return Collection<int, DailyPlan>
     */
    public function recentPlansForUser(int $userId, Carbon $beforeDate, int $limit = 14): Collection
    {
        return DailyPlan::where('user_id', $userId)
            ->whereDate('date', '<', $beforeDate->toDateString())
            ->withCount('tasks')
            ->orderByDesc('date')
            ->limit($limit)
            ->get();
    }
}
