<?php

namespace App\Livewire\Categories;

use App\Models\Category;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.app-shell')]
#[Title('Categories')]
class Index extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public string $icon = 'folder';

    public string $color = '#6366f1';

    public ?string $weekly_target_hours = '';

    public ?string $daily_target_hours = '';

    public bool $is_active = true;

    public bool $showArchiveConfirm = false;

    public ?int $archivingId = null;

    public string $filter = 'active';

    public function mount(): void {}

    /**
     * @return array<string, mixed>
     */
    protected function rules(bool $forUpdate = false): array
    {
        $userId = (int) Auth::id();
        $unique = Rule::unique('categories', 'name')->where('user_id', $userId)->ignore($forUpdate ? $this->editingId : null);

        return [
            'name' => ['required', 'string', 'max:80', $unique],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'regex:/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'weekly_target_hours' => ['nullable', 'numeric', 'min:0', 'max:168'],
            'daily_target_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Available Fluent icons selectable for a category.
     *
     * @return array<string, string>
     */
    #[Computed]
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

    /**
     * Color swatches selectable for a category.
     *
     * @return array<string, string>
     */
    #[Computed]
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

    public function edit(Category $category): void
    {
        $this->authorize('update', $category);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->icon = $category->icon;
        $this->color = $category->color;
        $this->weekly_target_hours = $category->weekly_target_hours ?? '';
        $this->daily_target_hours = $category->daily_target_hours ?? '';
        $this->is_active = (bool) $category->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->weekly_target_hours = $this->weekly_target_hours === '' ? null : $this->weekly_target_hours;
        $this->daily_target_hours = $this->daily_target_hours === '' ? null : $this->daily_target_hours;

        $updating = $this->editingId !== null;
        $validated = $this->validate($this->rules($updating));

        $validated['weekly_target_hours'] = $validated['weekly_target_hours'] ?: null;
        $validated['daily_target_hours'] = $validated['daily_target_hours'] ?: null;
        $validated['description'] = $validated['description'] ?: null;

        if ($updating) {
            $category = Category::findOrFail($this->editingId);
            $this->authorize('update', $category);
            $category->update($validated);
            Flux::toast(text: __('Category updated.'), variant: 'success');
        } else {
            $validated['user_id'] = Auth::id();
            Category::create($validated);
            Flux::toast(text: __('Category created.'), variant: 'success');
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function confirmArchive(Category $category): void
    {
        $this->authorize('delete', $category);
        if ($category->tasks()->exists()) {
            $this->archivingId = $category->id;
            $this->showArchiveConfirm = true;
        } else {
            $category->delete();
            Flux::toast(text: __('Category deleted.'), variant: 'success');
        }
    }

    public function archive(): void
    {
        $category = Category::findOrFail($this->archivingId);
        $this->authorize('update', $category);
        $category->update(['is_active' => false]);
        $this->showArchiveConfirm = false;
        $this->archivingId = null;
        Flux::toast(text: __('Category archived.'), variant: 'success');
    }

    public function restore(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->authorize('update', $category);
        $category->update(['is_active' => true]);
        Flux::toast(text: __('Category restored.'), variant: 'success');
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->authorize('delete', $category);
        $category->delete();
        Flux::toast(text: __('Category deleted.'), variant: 'success');
    }

    public function cancelForm(): void
    {
        $this->resetForm();
        $this->showForm = false;
    }

    protected function resetForm(): void
    {
        $this->reset(['name', 'description', 'icon', 'color', 'weekly_target_hours', 'daily_target_hours', 'is_active', 'editingId']);
        $this->icon = 'folder';
        $this->color = '#6366f1';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $query = Category::forUser((int) Auth::id())
            ->withCount('tasks');

        $query = $this->filter === 'archived'
            ? $query->where('is_active', false)
            : ($this->filter === 'all' ? $query : $query->where('is_active', true));

        $categories = $query->orderByDesc('id')->paginate(12);

        return view('livewire.categories.index', ['categories' => $categories]);
    }
}
