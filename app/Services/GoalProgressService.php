<?php

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Goal;

/**
 * Syncs goal progress from completed tasks.
 */
class GoalProgressService
{
    /**
     * Recalculate the current_value for a goal based on completed tasks.
     *
     * If the goal has a target_value, current_value is set to the count
     * of completed tasks (or sum of actual_minutes if target is time-based).
     * If the goal has no target_value, current_value is set to the count.
     */
    public function syncFromTasks(Goal $goal): Goal
    {
        if ($goal->target_value === null || (float) $goal->target_value <= 0) {
            return $goal;
        }

        $completedCount = $goal->tasks()->where('status', TaskStatus::Completed)->count();
        $totalActualMinutes = (int) $goal->tasks()->where('status', TaskStatus::Completed)->sum('actual_minutes');
        $totalTasks = $goal->tasks()->count();

        // If target_value is small (e.g., <= 100), treat it as a count-based goal.
        // Otherwise, treat it as time-based (minutes).
        $target = (float) $goal->target_value;

        if ($target <= 100) {
            $goal->current_value = (string) $completedCount;
        } else {
            // Time-based: sum of actual minutes
            $goal->current_value = (string) $totalActualMinutes;
        }

        // Auto-complete if reached
        if ((float) $goal->current_value >= $target && $goal->status === GoalStatus::Active) {
            $goal->status = GoalStatus::Completed;
        }

        $goal->save();

        return $goal->refresh();
    }

    /**
     * Sync all active goals for a user based on their tasks.
     *
     * @return int Number of goals updated
     */
    public function syncForUser(int $userId): int
    {
        $goals = Goal::forUser($userId)
            ->whereNotNull('target_value')
            ->get();

        $count = 0;
        foreach ($goals as $goal) {
            $this->syncFromTasks($goal);
            $count++;
        }

        return $count;
    }
}
