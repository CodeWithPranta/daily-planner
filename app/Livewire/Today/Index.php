<?php

namespace App\Livewire\Today;

use App\Actions\CompleteTaskAction;
use App\Actions\UpdateTaskProgressAction;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\DailyPlan;
use App\Models\Goal;
use App\Models\Task;
use App\Services\DailyPlanService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'today'])]
#[Title('Today')]
class Index extends Component
{
    public ?int $editingTaskId = null;

    public ?int $addingToPlanId = null;

    // Task form fields
    public string $title = '';

    public string $description = '';

    public ?int $category_id = null;

    public ?int $goal_id = null;

    public ?int $planned_minutes = 60;

    public string $priority = 'medium';

    // Plan copy / duplicate
    public ?int $copyFromPlanId = null;

    public bool $showCopySheet = false;

    private ?DailyPlan $todayPlanCache = null;

    public function mount(): void
    {
        $this->todayPlan();
        if (request()->query('add')) {
            $this->openAddForm();
        }
    }

    /**
     * Get or create today's daily plan for the authenticated user (cached per request).
     */
    protected function todayPlan(): ?DailyPlan
    {
        return $this->todayPlanCache ??= app(DailyPlanService::class)->getOrCreateFor(
            (int) Auth::id(),
            Carbon::today(),
        );
    }

    /**
     * Active categories for the current user, keyed by id.
     *
     * @return SupportCollection<int, string>
     */
    protected function categories(): SupportCollection
    {
        return Category::forUser((int) Auth::id())
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Active goals for the current user, keyed by id.
     *
     * @return SupportCollection<int, string>
     */
    protected function activeGoals(): SupportCollection
    {
        return Goal::forUser((int) Auth::id())
            ->active()
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    /**
     * Recent prior daily plans for the copy-day selector.
     *
     * @return Collection<int, DailyPlan>
     */
    protected function recentPlans(): Collection
    {
        return app(DailyPlanService::class)->recentPlansForUser((int) Auth::id(), Carbon::today(), 14);
    }

    public function openAddForm(): void
    {
        $this->resetTaskForm();
        $this->addingToPlanId = $this->todayPlan()->id;
    }

    public function openEdit(Task $task): void
    {
        $this->authorize('update', $task);
        $this->editingTaskId = $task->id;
        $this->addingToPlanId = null;
        $this->title = $task->title;
        $this->description = $task->description ?? '';
        $this->category_id = $task->category_id;
        $this->goal_id = $task->goal_id;
        $this->planned_minutes = $task->planned_minutes;
        $this->priority = $task->priority->value;
    }

    public function saveTask(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id,user_id,'.(int) Auth::id()],
            'goal_id' => ['nullable', 'integer', 'exists:goals,id,user_id,'.(int) Auth::id()],
            'planned_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $validated['description'] = $validated['description'] ?: null;
        $validated['category_id'] = $validated['category_id'] ?: null;
        $validated['goal_id'] = $validated['goal_id'] ?: null;

        if ($this->editingTaskId) {
            $task = Task::findOrFail($this->editingTaskId);
            $this->authorize('update', $task);
            $task->update($validated);
            Flux::toast(text: __('Task updated.'), variant: 'success');
        } else {
            app(DailyPlanService::class)->addTask($this->todayPlan(), $validated);
            Flux::toast(text: __('Task added.'), variant: 'success');
        }

        $this->resetTaskForm();
        $this->editingTaskId = null;
        $this->addingToPlanId = null;
        $this->todayPlanCache = null;
    }

    public function duplicateYesterday(): void
    {
        $yesterday = Carbon::yesterday();
        $origin = DailyPlan::forUser((int) Auth::id())->forDate($yesterday)->first();

        if (! $origin || $origin->tasks()->doesntExist()) {
            Flux::toast(text: __('No tasks found for yesterday to duplicate.'), variant: 'warning');

            return;
        }

        $count = app(DailyPlanService::class)->duplicateTasks($this->todayPlan(), $origin);
        $this->todayPlanCache = null;
        Flux::toast(
            text: __('Duplicated :count tasks from yesterday.', ['count' => $count]),
            variant: 'success',
        );
    }

    public function openCopySheet(): void
    {
        $this->showCopySheet = true;
    }

    public function copyPlan(): void
    {
        if (! $this->copyFromPlanId) {
            return;
        }

        $origin = DailyPlan::forUser((int) Auth::id())->findOrFail($this->copyFromPlanId);
        $this->authorize('view', $origin);
        $count = app(DailyPlanService::class)->duplicateTasks($this->todayPlan(), $origin);

        $this->showCopySheet = false;
        $this->copyFromPlanId = null;
        $this->todayPlanCache = null;
        Flux::toast(text: __('Copied :count tasks.', ['count' => $count]), variant: 'success');
    }

    public function deleteTask(int $id): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('delete', $task);
        $task->delete();
        $this->todayPlanCache = null;
        Flux::toast(text: __('Task removed.'), variant: 'success');
    }

    public function toggleComplete(int $id, CompleteTaskAction $completeAction, UpdateTaskProgressAction $progressAction): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);

        if ($task->status === TaskStatus::Completed) {
            $progressAction->execute($task, ['status' => TaskStatus::Pending, 'progress_percentage' => $task->progress_percentage]);
            $this->dispatch('task-updated', id: $id, status: 'pending');
            Flux::toast(text: __('Task reopened.'), variant: 'info');
        } else {
            $completeAction->execute($task, notes: __('Task completed.'));
            $this->dispatch('task-updated', id: $id, status: 'completed');
            Flux::toast(text: __('Task completed!'), variant: 'success');
        }

        $this->todayPlanCache = null;
    }

    public function skipTaskFromList(int $id, UpdateTaskProgressAction $action): void
    {
        $task = Task::findOrFail($id);
        $this->authorize('update', $task);
        $action->execute($task, ['status' => TaskStatus::Skipped, 'progress_percentage' => $task->progress_percentage]);
        $this->dispatch('task-updated', id: $id, status: 'skipped');
        $this->todayPlanCache = null;
        Flux::toast(text: __('Task skipped.'), variant: 'warning');
    }

    public function markPlan(): void
    {
        app(DailyPlanService::class)->markPlanned($this->todayPlan());
        $this->todayPlanCache = null;
        Flux::toast(text: __('Day marked as planned. Let’s get to work!'), variant: 'success');
    }

    public function cancelForm(): void
    {
        $this->resetTaskForm();
        $this->editingTaskId = null;
        $this->addingToPlanId = null;
    }

    protected function resetTaskForm(): void
    {
        $this->reset(['title', 'description', 'category_id', 'goal_id', 'planned_minutes', 'priority']);
        $this->planned_minutes = 60;
        $this->priority = 'medium';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $plan = $this->todayPlan();
        $tasks = $plan
            ? $plan->tasks()->with(['category', 'goal'])->orderBy('order_column')->get()
            : collect();

        $totalPlanned = (int) $tasks->sum('planned_minutes');

        return view('livewire.today.index', [
            'plan' => $plan,
            'tasks' => $tasks,
            'totalPlanned' => $totalPlanned,
            'categories' => $this->categories(),
            'activeGoals' => $this->activeGoals(),
            'recentPlans' => $this->recentPlans(),
        ]);
    }
}
