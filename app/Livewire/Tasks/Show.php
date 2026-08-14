<?php

namespace App\Livewire\Tasks;

use App\Actions\CompleteTaskAction;
use App\Actions\UpdateTaskProgressAction;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\TimerService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'today'])]
#[Title('Task')]
class Show extends Component
{
    public int $taskId;

    // Progress update form
    public string $notes = '';

    public int $progress = 0;

    public int $worked_hours = 0;

    public int $worked_minutes = 0;

    public string $status = 'in_progress';

    public ?string $reason = '';

    public bool $showUpdateForm = false;

    public function mount(Task $task): void
    {
        $this->authorize('view', $task);
        $this->taskId = $task->id;
        $this->progress = $task->progress_percentage;
        $this->status = $task->status->value;
    }

    protected function task(): Task
    {
        return Task::with(['category', 'goal', 'updates', 'timerSessions' => function ($q) {
            $q->where('status', '!=', 'stopped')->latest();
        }])->findOrFail($this->taskId);
    }

    public function openUpdateForm(): void
    {
        $this->showUpdateForm = true;
        $this->reset(['notes', 'reason', 'worked_hours', 'worked_minutes']);
        $this->progress = $this->task()->progress_percentage;
    }

    public function saveProgress(UpdateTaskProgressAction $action): void
    {
        $task = $this->task();
        $this->authorize('update', $task);

        $workedMinutes = ($this->worked_hours * 60) + $this->worked_minutes;

        $validated = $this->validate([
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
            'worked_hours' => ['required', 'integer', 'min:0', 'max:16'],
            'worked_minutes' => ['required', 'integer', 'min:0', 'max:59'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:pending,in_progress,completed,partially_completed,skipped'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $notes = $validated['notes'];
        if (in_array($validated['status'], ['partially_completed', 'skipped'], true) && $validated['reason']) {
            $notes = trim(($notes ?? '')."\n\nReason: ".$validated['reason']);
        }

        $action->execute($task, [
            'progress_percentage' => $validated['progress'],
            'worked_minutes' => $workedMinutes,
            'notes' => $notes,
            'status' => $validated['status'],
        ]);

        $this->showUpdateForm = false;
        $this->reset(['notes', 'reason', 'worked_hours', 'worked_minutes']);
        $this->progress = $this->task()->progress_percentage;

        Flux::toast(text: __('Progress logged.'), variant: 'success');
    }

    public function completeTask(CompleteTaskAction $action): void
    {
        $task = $this->task();
        $this->authorize('update', $task);
        $action->execute($task, notes: __('Task completed.'));
        Flux::toast(text: __('Task completed! 🎉'), variant: 'success');
    }

    public function skipTask(UpdateTaskProgressAction $action): void
    {
        $task = $this->task();
        $this->authorize('update', $task);
        $action->execute($task, ['status' => TaskStatus::Skipped, 'progress_percentage' => $task->progress_percentage]);
        Flux::toast(text: __('Task skipped.'), variant: 'warning');
    }

    public function startTimer(TimerService $timerService): void
    {
        $task = $this->task();
        $this->authorize('update', $task);
        $timerService->startForTask($task);
        $this->dispatch('timer-started');
        Flux::toast(text: __('Timer started.'), variant: 'success');
    }

    public function render(): View
    {
        $task = $this->task();
        $updates = $task->updates()->orderByDesc('created_at')->limit(20)->get();
        $hasActiveTimer = $task->timerSessions()
            ->whereIn('status', ['running', 'paused'])
            ->exists();

        return view('livewire.tasks.show', [
            'task' => $task,
            'updates' => $updates,
            'hasActiveTimer' => $hasActiveTimer,
        ]);
    }
}
