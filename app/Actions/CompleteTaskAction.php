<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\GoalProgressService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Mark a task as completed, recording a progress update and syncing goal progress.
 */
class CompleteTaskAction
{
    public function __construct(
        private GoalProgressService $goalProgressService,
    ) {}

    /**
     * Execute the action.
     */
    public function execute(Task $task, ?string $notes = null, ?int $workedMinutes = null): Task
    {
        return DB::transaction(function () use ($task, $notes, $workedMinutes): Task {
            $workedMinutes ??= 0;

            $task->updates()->create([
                'progress_percentage' => 100,
                'worked_minutes' => $workedMinutes,
                'notes' => $notes,
                'status' => TaskStatus::Completed,
            ]);

            $task->progress_percentage = 100;
            $task->status = TaskStatus::Completed;
            $task->completed_at = Carbon::now();
            $task->actual_minutes += $workedMinutes;
            $task->save();

            // Sync goal progress if this task belongs to a goal
            if ($task->goal_id) {
                $task->load('goal');
                if ($task->goal) {
                    $this->goalProgressService->syncFromTasks($task->goal);
                }
            }

            return $task->refresh();
        });
    }
}
