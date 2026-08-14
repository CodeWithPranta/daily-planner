<div>
    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Categories') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Organize your life by area of focus.') }}</p>
        </div>
        <button type="button" wire:click="create" class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition active:scale-95">
            <flux:icon name="plus" class="size-4" variant="solid" />
            <span class="max-sm:hidden">{{ __('New') }}</span>
        </button>
    </div>

    {{-- Filter chips --}}
    <div class="mb-4 flex gap-2">
        @php
            $filters = [
                'active' => __('Active'),
                'archived' => __('Archived'),
                'all' => __('All'),
            ];
        @endphp
        @foreach ($filters as $key => $label)
            <button type="button" wire:click="$set('filter', '{{ $key }}')"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ $filter === $key ? 'bg-brand-600 text-white' : 'bg-white text-zinc-600 border border-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:border-zinc-800' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Create/Edit form modal --}}
    <flux:modal name="category-form" wire:model="showForm" focusable class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit Category') : __('New Category') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" placeholder="BCS Study" required autofocus />

            <flux:textarea wire:model="description" :label="__('Description')" placeholder="{{ __('Optional notes about this category') }}" />

            <div class="grid grid-cols-2 gap-3">
                <flux:select wire:model="icon" :label="__('Icon')">
                    @foreach ($this->iconOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <div class="grid gap-1.5">
                    <flux:label>{{ __('Color') }}</flux:label>
                    <div class="flex flex-wrap gap-2 pt-0.5">
                        @foreach ($this->colorOptions as $hex => $name)
                            <button type="button" wire:click="$set('color', '{{ $hex }}')"
                                class="size-8 rounded-full ring-offset-2 ring-offset-white transition dark:ring-offset-zinc-900 {{ $color === $hex ? 'ring-2 ring-zinc-900 dark:ring-white' : '' }}"
                                style="background-color: {{ $hex }}" aria-label="{{ $name }}"></button>
                        @endforeach
                    </div>
                    @error('color') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="daily_target_hours" :label="__('Daily target (hrs)')" type="number" step="0.25" min="0" max="24" placeholder="2" />
                <flux:input wire:model="weekly_target_hours" :label="__('Weekly target (hrs)')" type="number" step="0.25" min="0" max="168" placeholder="14" />
            </div>

            @if ($editingId)
                <flux:checkbox wire:model="is_active" :label="__('Active')" />
            @endif

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $editingId ? __('Save') : __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Archive confirmation modal --}}
    <flux:modal name="archive-confirm" wire:model="showArchiveConfirm" focusable class="max-w-md">
        <flux:heading size="lg">{{ __('Archive category?') }}</flux:heading>
        <flux:text class="mt-2">{{ __('This category has historical task data. Archiving keeps your history intact while hiding it from active planning. You can restore it later.') }}</flux:text>
        <div class="mt-4 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="$set('showArchiveConfirm', false)">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="archive">{{ __('Archive') }}</flux:button>
        </div>
    </flux:modal>

    {{-- List --}}
    @if ($categories->isEmpty())
        <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="folder-plus" class="size-8 text-zinc-300 dark:text-zinc-700" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $filter === 'archived' ? __('No archived categories.') : __('Create your first category to get started.') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($categories as $category)
                <x-card padding="false" class="overflow-hidden" wire:key="cat-{{ $category->id }}">
                    <div class="flex items-center gap-3 p-4">
                        <span class="flex size-11 flex-none items-center justify-center rounded-xl text-white" style="background-color: {{ $category->color }}">
                            <flux:icon name="{{ $category->icon }}" class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-semibold">{{ $category->name }}</p>
                                @if (! $category->is_active)
                                    <span class="rounded-full bg-zinc-200 px-2 py-0.5 text-[10px] font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">{{ __('Archived') }}</span>
                                @endif
                            </div>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $category->tasks_count }} {{ __('tasks') }}
                                @if ($category->daily_target_hours) · {{ __('Target') }} {{ $category->daily_target_hours }}h{{ __('/day') }} @endif
                            </p>
                            @if ($category->description)
                                <p class="mt-0.5 line-clamp-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $category->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-none items-center gap-1">
                            <button type="button" wire:click="edit({{ $category->id }})" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Edit') }}">
                                <flux:icon name="pencil" class="size-5" />
                            </button>
                            @if ($category->is_active)
                                <button type="button" wire:click="confirmArchive({{ $category->id }})" wire:loading.attr="disabled" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Archive') }}">
                                    <flux:icon name="archive-box" class="size-5" />
                                </button>
                            @else
                                <button type="button" wire:click="restore({{ $category->id }})" class="rounded-lg p-2 text-emerald-600 transition hover:bg-emerald-50 dark:hover:bg-emerald-500/10" aria-label="{{ __('Restore') }}">
                                    <flux:icon name="arrow-path" class="size-5" />
                                </button>
                                <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="{{ __('Permanently delete this category?') }}" class="rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="{{ __('Delete') }}">
                                    <flux:icon name="trash" class="size-5" />
                                </button>
                            @endif
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-4">{{ $categories->links() }}</div>
    @endif
</div>