<?php

namespace App\Actions;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\DailyProductivityScoreService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Record a progress update on a task and adjust its status / accumulated time.
 */
class UpdateTaskProgressAction
{
    public function __construct(
        private DailyProductivityScoreService $scoreService,
    ) {}

    /**
     * Execute the action.
     *
     * @param  array{progress_percentage?: int, worked_minutes?: int, notes?: string|null, status?: TaskStatus|string}  $data
     */
    public function execute(Task $task, array $data): Task
    {
        return DB::transaction(function () use ($task, $data): Task {
            $progress = (int) ($data['progress_percentage'] ?? $task->progress_percentage);
            $workedMinutes = (int) ($data['worked_minutes'] ?? 0);
            $notes = $data['notes'] ?? null;

            /** @var TaskStatus|string $statusInput */
            $statusInput = $data['status'] ?? $task->status;
            $status = $statusInput instanceof TaskStatus
                ? $statusInput
                : TaskStatus::from($statusInput);

            $task->updates()->create([
                'progress_percentage' => $progress,
                'worked_minutes' => $workedMinutes,
                'notes' => $notes,
                'status' => $status,
            ]);

            $task->progress_percentage = $progress;
            $task->actual_minutes += $workedMinutes;
            $task->status = $status;

            if ($status === TaskStatus::Completed) {
                $task->progress_percentage = 100;
                $task->completed_at = Carbon::now();
            }

            if ($status === TaskStatus::InProgress && ! $task->started_at) {
                $task->started_at = Carbon::now();
            }

            $task->save();

            // Keep the cached daily score (and therefore streak/analytics) in sync.
            $this->scoreService->refreshForTask($task);

            return $task->refresh();
        });
    }
}
