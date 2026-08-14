<?php

namespace App\Livewire\Dashboard;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Task;
use App\Services\DailyProductivityScoreService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.app-shell', ['active' => 'home'])]
#[Title('Dashboard')]
class Index extends Component
{
    public bool $showCategoryForm = false;

    public string $catName = '';

    public string $catColor = '#6366f1';

    public string $catIcon = 'folder';

    public bool $showGoalForm = false;

    public string $goalTitle = '';

    public ?int $goalCategoryId = null;

    public ?string $goalTargetValue = '';

    public function createCategory(): void
    {
        $this->reset(['catName', 'catColor', 'catIcon']);
        $this->catColor = '#6366f1';
        $this->catIcon = 'folder';
        $this->showCategoryForm = true;
        $this->showGoalForm = false;
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'catName' => ['required', 'string', 'max:80'],
            'catColor' => ['required', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'catIcon' => ['required', 'string', 'max:80'],
        ]);

        Category::create([
            'user_id' => Auth::id(),
            'name' => $validated['catName'],
            'color' => $validated['catColor'],
            'icon' => $validated['catIcon'],
            'is_active' => true,
        ]);

        $this->showCategoryForm = false;
        $this->reset(['catName', 'catColor', 'catIcon']);
        Flux::toast(text: __('Category created.'), variant: 'success');
    }

    public function cancelCategoryForm(): void
    {
        $this->showCategoryForm = false;
        $this->reset(['catName', 'catColor', 'catIcon']);
        $this->resetErrorBag();
    }

    public function createGoal(): void
    {
        $this->reset(['goalTitle', 'goalCategoryId', 'goalTargetValue']);
        $this->showGoalForm = true;
        $this->showCategoryForm = false;
    }

    public function saveGoal(): void
    {
        $validated = $this->validate([
            'goalTitle' => ['required', 'string', 'max:120'],
            'goalCategoryId' => ['nullable', 'integer', 'exists:categories,id,user_id,'.(int) Auth::id()],
            'goalTargetValue' => ['nullable', 'numeric', 'min:0'],
        ]);

        Goal::create([
            'user_id' => Auth::id(),
            'title' => $validated['goalTitle'],
            'category_id' => $validated['goalCategoryId'] ?: null,
            'target_value' => $validated['goalTargetValue'] !== '' ? $validated['goalTargetValue'] : null,
            'status' => 'active',
        ]);

        $this->showGoalForm = false;
        $this->reset(['goalTitle', 'goalCategoryId', 'goalTargetValue']);
        Flux::toast(text: __('Goal created.'), variant: 'success');
    }

    public function cancelGoalForm(): void
    {
        $this->showGoalForm = false;
        $this->reset(['goalTitle', 'goalCategoryId', 'goalTargetValue']);
        $this->resetErrorBag();
    }

    /**
     * Active categories for the current user, keyed by id.
     *
     * @return SupportCollection<int, string>
     */
    public function categories(): SupportCollection
    {
        return Category::forUser((int) Auth::id())
            ->active()
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * @return array<string, string>
     */
    public function colorOptions(): array
    {
        return [
            '#6366f1' => __('Indigo'),
            '#0ea5e9' => __('Sky'),
            '#10b981' => __('Emerald'),
            '#f59e0b' => __('Amber'),
            '#ef4444' => __('Rose'),
            '#8b5cf6' => __('Violet'),
            '#ec4899' => __('Pink'),
            '#64748b' => __('Slate'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function iconOptions(): array
    {
        return [
            'folder' => __('Folder'),
            'book-open' => __('Book'),
            'code-bracket' => __('Code'),
            'language' => __('Language'),
            'heart' => __('Health'),
            'home' => __('Family'),
            'briefcase' => __('Work'),
            'rocket-launch' => __('Goals'),
            'puzzle-piece' => __('Other'),
        ];
    }

    public function render(): View
    {
        $user = Auth::user();
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good Morning') : ($hour < 17 ? __('Good Afternoon') : __('Good Evening'));
        $today = now()->translatedFormat('l, F j');

        $scoreService = app(DailyProductivityScoreService::class);
        $score = $scoreService->calculateForUser((int) $user->id, Carbon::today());

        $completionPercent = $score->completionPercent();

        $plannedH = intdiv($score->totalPlannedMinutes, 60);
        $plannedM = $score->totalPlannedMinutes % 60;
        $actualH = intdiv($score->totalActualMinutes, 60);
        $actualM = $score->totalActualMinutes % 60;
        $timePercent = $score->totalPlannedMinutes > 0
            ? (int) min(100, round($score->totalActualMinutes / $score->totalPlannedMinutes * 100))
            : 0;

        $startableTask = Task::forUser((int) $user->id)
            ->forDate(Carbon::today())
            ->whereIn('status', [TaskStatus::Pending, TaskStatus::InProgress])
            ->orderBy('order_column')
            ->first();

        return view('livewire.dashboard.index', [
            'greeting' => $greeting,
            'todayLabel' => $today,
            'userName' => $user->name,
            'score' => $score,
            'completionPercent' => $completionPercent,
            'plannedH' => $plannedH,
            'plannedM' => $plannedM,
            'actualH' => $actualH,
            'actualM' => $actualM,
            'timePercent' => $timePercent,
            'categories' => $this->categories(),
            'colorOptions' => $this->colorOptions(),
            'iconOptions' => $this->iconOptions(),
            'startableTask' => $startableTask,
        ]);
    }
}
