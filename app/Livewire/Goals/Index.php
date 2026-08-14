<?php

namespace App\Livewire\Goals;

use App\Enums\GoalStatus;
use App\Models\Category;
use App\Models\Goal;
use Flux\Flux;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-shell')]
#[Title('Goals')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $category_id = null;

    public string $title = '';

    public string $description = '';

    public ?string $target_date = '';

    public ?string $target_value = '';

    public ?string $current_value = '';

    public string $status = 'active';

    public string $filter = 'all';

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $forUpdate = false): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id,user_id,'.(int) Auth::id()],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'target_date' => ['nullable', 'date'],
            'target_value' => ['nullable', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,completed,paused,archived'],
        ];
    }

    /**
     * Active categories for the current user, keyed by id.
     *
     * @return SupportCollection<int, string>
     */
    #[Computed]
    public function categories(): SupportCollection
    {
        return Category::forUser((int) Auth::id())->active()->orderBy('name')->pluck('name', 'id');
    }

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showForm = true;
    }

    public function edit(Goal $goal): void
    {
        $this->authorize('update', $goal);
        $this->editingId = $goal->id;
        $this->category_id = $goal->category_id;
        $this->title = $goal->title;
        $this->description = $goal->description ?? '';
        $this->target_date = $goal->target_date?->format('Y-m-d') ?? '';
        $this->target_value = $goal->target_value !== null ? (string) $goal->target_value : '';
        $this->current_value = $goal->current_value !== null ? (string) $goal->current_value : '';
        $this->status = $goal->status->value;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate($this->rules($this->editingId !== null));
        $validated['target_date'] = $validated['target_date'] ?: null;
        $validated['target_value'] = $validated['target_value'] !== '' ? $validated['target_value'] : null;
        $validated['current_value'] = $validated['current_value'] !== '' ? $validated['current_value'] : null;

        if ($this->editingId) {
            $goal = Goal::findOrFail($this->editingId);
            $this->authorize('update', $goal);
            $goal->update($validated);
            Flux::toast(text: __('Goal updated.'), variant: 'success');
        } else {
            $validated['user_id'] = Auth::id();
            Goal::create($validated);
            Flux::toast(text: __('Goal created.'), variant: 'success');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function complete(int $id): void
    {
        $goal = Goal::findOrFail($id);
        $this->authorize('update', $goal);
        $goal->update(['status' => GoalStatus::Completed, 'current_value' => $goal->current_value ?? $goal->target_value]);
        Flux::toast(text: __('Goal completed. 🎉'), variant: 'success');
    }

    public function delete(int $id): void
    {
        $goal = Goal::findOrFail($id);
        $this->authorize('delete', $goal);
        $goal->delete();
        Flux::toast(text: __('Goal deleted.'), variant: 'success');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['category_id', 'title', 'description', 'target_date', 'target_value', 'current_value', 'status', 'editingId']);
        $this->status = 'active';
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $query = Goal::forUser((int) Auth::id())->with('category');

        $query = match ($this->filter) {
            'active' => $query->active(),
            'completed' => $query->where('status', GoalStatus::Completed),
            'archived' => $query->where('status', GoalStatus::Archived),
            default => $query,
        };

        $goals = $query->orderByDesc('id')->paginate(10);

        return view('livewire.goals.index', ['goals' => $goals]);
    }
}
