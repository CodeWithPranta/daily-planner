<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Enums\TimerStatus;
use App\Models\Task;
use App\Models\TaskUpdate;
use App\Models\TimerSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Manages timer sessions: start, pause, resume, stop, and automatic task progress logging.
 */
class TimerService
{
    /**
     * Start a new timer for a task. Stops any existing active timer for the user first.
     */
    public function startForTask(Task $task): TimerSession
    {
        return DB::transaction(function () use ($task) {
            $this->stopAllActiveForUser($task->user_id);

            if (! $task->started_at) {
                $task->update(['started_at' => Carbon::now(), 'status' => TaskStatus::InProgress]);
            }

            return TimerSession::create([
                'user_id' => $task->user_id,
                'task_id' => $task->id,
                'started_at' => Carbon::now(),
                'total_seconds' => 0,
                'status' => TimerStatus::Running,
            ]);
        });
    }

    /**
     * Pause a running timer.
     */
    public function pause(TimerSession $session): TimerSession
    {
        if ($session->status !== TimerStatus::Running) {
            return $session;
        }

        return DB::transaction(function () use ($session) {
            $now = Carbon::now();
            $segmentStart = $session->resumed_at ?? $session->started_at;
            $session->total_seconds += (int) $segmentStart->diffInSeconds($now);
            $session->paused_at = $now;
            $session->resumed_at = null;
            $session->status = TimerStatus::Paused;
            $session->save();

            return $session->refresh();
        });
    }

    /**
     * Resume a paused timer.
     */
    public function resume(TimerSession $session): TimerSession
    {
        if ($session->status !== TimerStatus::Paused) {
            return $session;
        }

        return DB::transaction(function () use ($session) {
            $session->resumed_at = Carbon::now();
            $session->paused_at = null;
            $session->status = TimerStatus::Running;
            $session->save();

            return $session->refresh();
        });
    }

    /**
     * Stop a timer and create a task progress update with the elapsed time.
     */
    public function stop(TimerSession $session, ?string $notes = null, int $progressPercentage = -1): TimerSession
    {
        if ($session->status === TimerStatus::Stopped) {
            return $session;
        }

        return DB::transaction(function () use ($session, $notes, $progressPercentage) {
            $now = Carbon::now();

            if ($session->status === TimerStatus::Running) {
                $segmentStart = $session->resumed_at ?? $session->started_at;
                $session->total_seconds += (int) $segmentStart->diffInSeconds($now);
            }

            $session->ended_at = $now;
            $session->status = TimerStatus::Stopped;
            $session->save();

            $task = $session->task;
            $workedMinutes = (int) round($session->total_seconds / 60);

            if ($workedMinutes > 0 || $progressPercentage >= 0) {
                $this->logTaskProgress($session->task, $workedMinutes, $notes, $progressPercentage);
            }

            $task->actual_minutes += $workedMinutes;
            if ($progressPercentage >= 0 && $progressPercentage <= 100) {
                $task->progress_percentage = $progressPercentage;
                if ($progressPercentage >= 100) {
                    $task->status = TaskStatus::Completed;
                    $task->completed_at = $now;
                } elseif ($progressPercentage > 0) {
                    $task->status = TaskStatus::InProgress;
                }
            }
            $task->save();

            return $session->refresh();
        });
    }

    /**
     * Get the active (running or paused) timer for a user, if any.
     */
    public function getActiveForUser(int $userId): ?TimerSession
    {
        return TimerSession::forUser($userId)
            ->active()
            ->with('task.category')
            ->latest()
            ->first();
    }

    /**
     * Stop all active timers for a user.
     */
    public function stopAllActiveForUser(int $userId): void
    {
        $active = TimerSession::forUser($userId)->active()->get();

        foreach ($active as $session) {
            $this->stop($session);
        }
    }

    /**
     * Create a TaskUpdate record for the elapsed time.
     */
    private function logTaskProgress(Task $task, int $workedMinutes, ?string $notes, int $progressPercentage): TaskUpdate
    {
        $progressUpdate = $progressPercentage >= 0 && $progressPercentage <= 100
            ? $progressPercentage
            : $task->progress_percentage;

        $status = $progressUpdate >= 100
            ? TaskStatus::Completed
            : ($progressUpdate > 0 ? TaskStatus::InProgress : TaskStatus::Pending);

        return $task->updates()->create([
            'progress_percentage' => $progressUpdate,
            'worked_minutes' => $workedMinutes,
            'notes' => $notes,
            'status' => $status,
        ]);
    }
}
