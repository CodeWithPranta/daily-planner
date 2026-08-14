<div>
    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('Goals') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Long-term outcomes you are working toward.') }}</p>
        </div>
        <button type="button" wire:click="create" class="inline-flex items-center gap-1.5 rounded-full bg-brand-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition active:scale-95">
            <flux:icon name="plus" class="size-4" variant="solid" />
            <span class="max-sm:hidden">{{ __('New') }}</span>
        </button>
    </div>

    {{-- Filter chips --}}
    <div class="mb-4 flex gap-2">
        @php $filters = ['all' => __('All'), 'active' => __('Active'), 'completed' => __('Completed'), 'archived' => __('Archived')]; @endphp
        @foreach ($filters as $key => $label)
            <button type="button" wire:click="$set('filter', '{{ $key }}')"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition {{ $filter === $key ? 'bg-brand-600 text-white' : 'bg-white text-zinc-600 border border-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:border-zinc-800' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Form modal --}}
    <flux:modal name="goal-form" wire:model="showForm" focusable class="max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? __('Edit Goal') : __('New Goal') }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" placeholder="Become a Laravel Expert" required autofocus />

            <flux:textarea wire:model="description" :label="__('Description')" placeholder="{{ __('What does success look like?') }}" />

            <flux:select wire:model="category_id" :label="__('Category')">
                <option value="">{{ __('None') }}</option>
                @foreach ($this->categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </flux:select>

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="target_date" :label="__('Target date')" type="date" />
                <flux:select wire:model="status" :label="__('Status')">
                    @foreach (\App\Enums\GoalStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <flux:input wire:model="target_value" :label="__('Target value')" type="number" step="0.01" min="0" placeholder="100" />
                <flux:input wire:model="current_value" :label="__('Current value')" type="number" step="0.01" min="0" placeholder="42" />
            </div>
            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('If you set a target value, your progress is calculated automatically.') }}</p>

            <div class="flex justify-end gap-2 pt-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">{{ $editingId ? __('Save') : __('Create') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- List --}}
    @if ($goals->isEmpty())
        <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="rocket-launch" class="size-8 text-zinc-300 dark:text-zinc-700" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Define a goal to give your daily tasks purpose.') }}</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($goals as $goal)
                <x-card padding="false" class="overflow-hidden" wire:key="goal-{{ $goal->id }}">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-semibold">{{ $goal->title }}</p>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ \App\Concerns\HasColorBadge::badgeClasses($goal->status->color()) }}">{{ $goal->status->label() }}</span>
                                </div>
                                @if ($goal->description)
                                    <p class="mt-1 line-clamp-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $goal->description }}</p>
                                @endif
                                @if ($goal->category)
                                    <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $goal->category->name }}</p>
                                @endif
                            </div>
                            <div class="flex flex-none items-center gap-1">
                                <button type="button" wire:click="edit({{ $goal->id }})" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Edit') }}">
                                    <flux:icon name="pencil" class="size-5" />
                                </button>
                                @if ($goal->status !== \App\Enums\GoalStatus::Completed)
                                    <button type="button" wire:click="complete({{ $goal->id }})" class="rounded-lg p-2 text-emerald-600 transition hover:bg-emerald-50 dark:hover:bg-emerald-500/10" aria-label="{{ __('Mark complete') }}">
                                        <flux:icon name="check-badge" class="size-5" />
                                    </button>
                                @endif
                                <button type="button" wire:click="delete({{ $goal->id }})" wire:confirm="{{ __('Delete this goal?') }}" class="rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="{{ __('Delete') }}">
                                    <flux:icon name="trash" class="size-5" />
                                </button>
                            </div>
                        </div>

                        {{-- Progress --}}
                        @php $pct = $goal->progress_percentage; @endphp
                        <div class="mt-3 flex items-center gap-4">
                            <x-progress-ring :value="$pct" :size="56" :stroke="6" />
                            <div class="flex-1">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Progress') }}</span>
                                    <span class="font-semibold">{{ $pct }}%</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                                    <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $pct }}%"></div>
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] text-zinc-400 dark:text-zinc-500">
                                    <span>@if ($goal->current_value) {{ $goal->current_value }} @endif / @if ($goal->target_value) {{ $goal->target_value }} @else {{ __('—') }} @endif</span>
                                    @if ($goal->target_date) <span>{{ $goal->target_date->translatedFormat('M j, Y') }}</span> @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>

        <div class="mt-4">{{ $goals->links() }}</div>
    @endif
</div>