<?php

namespace App\Livewire\Review;

use App\Enums\DailyPlanStatus;
use App\Enums\TaskStatus;
use App\Models\DailyPlan;
use App\Models\Task;
use App\Services\DailyProductivityScoreService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'home'])]
#[Title('End of Day Review')]
class Daily extends Component
{
    public string $reflection = '';

    public ?int $reviewDateOffset = null;

    public function mount(?int $dateOffset = null): void
    {
        $this->reviewDateOffset = $dateOffset;
    }

    public function submitReview(DailyProductivityScoreService $scoreService): void
    {
        $userId = (int) Auth::id();
        $date = $this->getReviewDate();

        $score = $scoreService->storeForUser($userId, $date, $this->reflection);

        $plan = DailyPlan::forUser($userId)->forDate($date)->first();
        if ($plan) {
            $plan->update(['status' => DailyPlanStatus::Reviewed]);
        }

        $this->reflection = '';

        Flux::toast(text: __('Daily review saved!'), variant: 'success');
    }

    public function completeDay(DailyProductivityScoreService $scoreService): void
    {
        $userId = (int) Auth::id();
        $date = $this->getReviewDate();

        $score = $scoreService->storeForUser($userId, $date, $this->reflection);

        $plan = DailyPlan::forUser($userId)->forDate($date)->first();
        if ($plan) {
            $plan->update(['status' => DailyPlanStatus::Completed]);
        }

        Flux::toast(text: __('Day completed! Great work!'), variant: 'success');
    }

    protected function getReviewDate(): Carbon
    {
        if ($this->reviewDateOffset !== null) {
            return Carbon::today()->subDays($this->reviewDateOffset);
        }

        return Carbon::today();
    }

    public function render(): View
    {
        $userId = (int) Auth::id();
        $date = $this->getReviewDate();

        $scoreService = app(DailyProductivityScoreService::class);
        $score = $scoreService->calculateForUser($userId, $date);

        $tasks = Task::forUser($userId)
            ->forDate($date)
            ->with(['category', 'goal'])
            ->orderBy('order_column')
            ->orderBy('id')
            ->get();

        $plan = DailyPlan::forUser($userId)->forDate($date)->first();

        $completedTasks = $tasks->where('status', TaskStatus::Completed);
        $inProgressTasks = $tasks->where('status', TaskStatus::InProgress);
        $pendingTasks = $tasks->where('status', TaskStatus::Pending);
        $skippedTasks = $tasks->where('status', TaskStatus::Skipped);
        $partialTasks = $tasks->where('status', TaskStatus::PartiallyCompleted);

        $dateLabel = $date->isToday() ? 'Today' : $date->translatedFormat('l, F j');

        return view('livewire.review.daily', [
            'date' => $date,
            'dateLabel' => $dateLabel,
            'score' => $score,
            'tasks' => $tasks,
            'plan' => $plan,
            'completedTasks' => $completedTasks,
            'inProgressTasks' => $inProgressTasks,
            'pendingTasks' => $pendingTasks,
            'skippedTasks' => $skippedTasks,
            'partialTasks' => $partialTasks,
        ]);
    }
}
