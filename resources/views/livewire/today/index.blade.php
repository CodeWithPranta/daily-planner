<div>
    @php
        $today = now()->translatedFormat('l, F j, Y');
        $plannedH = intdiv($totalPlanned, 60);
        $plannedM = $totalPlanned % 60;
    @endphp

    {{-- Header --}}
    <div class="mb-4 flex items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __("Today") }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $today }}</p>
        </div>
        <div class="flex flex-none flex-col items-end gap-1">
            @if ($plan && $plan->status === \App\Enums\DailyPlanStatus::Draft)
                <button type="button" wire:click="markPlan" class="inline-flex items-center gap-1 rounded-full bg-brand-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition active:scale-95">
                    <flux:icon name="check" class="size-4" variant="solid" />
                    {{ __('Mark planned') }}
                </button>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1.5 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                    <flux:icon name="check-circle" class="size-4" variant="solid" />
                    {{ __('Planned') }}
                </span>
            @endif
        </div>
    </div>

    {{-- Plan status banner --}}
    @if ($plan && $plan->status === \App\Enums\DailyPlanStatus::Draft && $tasks->isEmpty())
        <x-card padding="false" class="mb-4 overflow-hidden">
            <div class="flex flex-col gap-3 p-4">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                        <flux:icon name="clipboard-document-list" variant="solid" class="size-5" />
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold">{{ __('Start your morning by planning') }}</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Duplicate yesterday, copy a previous day, or add tasks below.') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="duplicateYesterday" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white p-3 text-sm font-medium transition active:scale-95 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="arrow-path" class="size-4" /> {{ __('Yesterday') }}
                    </button>
                    <button type="button" wire:click="openCopySheet" class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-white p-3 text-sm font-medium transition active:scale-95 dark:border-zinc-800 dark:bg-zinc-900">
                        <flux:icon name="document-duplicate" class="size-4" /> {{ __('Copy day') }}
                    </button>
                </div>
            </div>
        </x-card>
    @endif

    {{-- Summary bar --}}
    @if ($tasks->isNotEmpty())
        <div class="mb-4 flex items-center justify-between rounded-2xl bg-brand-50 px-4 py-3 text-sm dark:bg-brand-500/10">
            <span class="flex items-center gap-2 font-medium text-brand-700 dark:text-brand-300">
                <flux:icon name="clock" class="size-4" />
                {{ $tasks->count() }} {{ __('tasks') }}
            </span>
            <span class="text-brand-700 dark:text-brand-300">@if($plannedH) {{ $plannedH }}h @endif @if($plannedM) {{ $plannedM }}m @endif</span>
        </div>
    @endif

    {{-- Add task button / form toggle --}}
    @if (! $editingTaskId && ! $addingToPlanId)
        <button type="button" wire:click="openAddForm" class="mb-4 flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-zinc-300 bg-white p-4 text-sm font-medium text-zinc-600 transition active:scale-95 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
            <flux:icon name="plus-circle" class="size-5 text-brand-600 dark:text-brand-400" />
            {{ __('Add task') }}
        </button>
    @endif

    {{-- Task form --}}
    @if ($addingToPlanId || $editingTaskId)
        <x-card padding="false" class="mb-4 overflow-hidden">
            <form wire:submit="saveTask" class="space-y-4 p-4">
                <div class="flex items-center justify-between">
                    <flux:heading>{{ $editingTaskId ? __('Edit task') : __('New task') }}</flux:heading>
                    <button type="button" wire:click="cancelForm" class="rounded-lg p-1.5 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Close') }}">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>

                <flux:input wire:model="title" :label="__('Title')" placeholder="Study Bangladesh Affairs" required autofocus />

                <flux:textarea wire:model="description" :label="__('Description')" placeholder="{{ __('Optional details') }}" />

                <div class="grid grid-cols-2 gap-3">
                    <flux:select wire:model="category_id" :label="__('Category')">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($categories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model="goal_id" :label="__('Goal')">
                        <option value="">{{ __('None') }}</option>
                        @foreach ($activeGoals as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:select wire:model="priority" :label="__('Priority')">
                    @foreach (\App\Enums\TaskPriority::cases() as $p)
                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                    @endforeach
                </flux:select>

                <div class="grid gap-1.5">
                    <flux:label>{{ __('Planned time') }}</flux:label>
                    <div class="flex items-center gap-2">
                        <input type="range" min="5" max="480" step="5" wire:model.live="planned_minutes" class="flex-1 accent-brand-600" />
                        <span class="w-20 text-right text-sm font-medium">@if(intdiv($planned_minutes,60)) {{ intdiv($planned_minutes,60) }}h @endif @if($planned_minutes%60) {{ $planned_minutes%60 }}m @endif</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <flux:button type="button" variant="ghost" wire:click="cancelForm">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ $editingTaskId ? __('Save') : __('Add task') }}</flux:button>
                </div>
            </form>
        </x-card>
    @endif

    {{-- Copy plan sheet --}}
    <flux:modal name="copy-sheet" wire:model="showCopySheet" focusable class="max-w-md">
        <flux:heading size="lg">{{ __('Copy a previous day') }}</flux:heading>
        <flux:text class="mt-1">{{ __('Select a past plan. Its tasks will be added to today.') }}</flux:text>
        @if ($recentPlans->isEmpty())
            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No previous plans yet.') }}</p>
        @else
            <div class="mt-4 max-h-64 space-y-2 overflow-y-auto">
                @foreach ($recentPlans as $prev)
                    <button type="button" wire:click="$set('copyFromPlanId', {{ $prev->id }})"
                        class="flex w-full items-center justify-between rounded-xl border p-3 text-left transition {{ (int)$copyFromPlanId === $prev->id ? 'border-brand-500 bg-brand-50 dark:bg-brand-500/10' : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900' }}">
                        <div>
                            <p class="text-sm font-medium">{{ $prev->date->translatedFormat('D, M j') }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $prev->tasks_count }} {{ __('tasks') }}</p>
                        </div>
                        @if ((int)$copyFromPlanId === $prev->id)
                            <flux:icon name="check-circle" class="size-5 text-brand-600" variant="solid" />
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
        <div class="mt-4 flex justify-end gap-2">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="$set('showCopySheet', false)">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button variant="primary" wire:click="copyPlan" :disabled="! $copyFromPlanId">{{ __('Copy') }}</flux:button>
        </div>
    </flux:modal>

    {{-- Tasks list --}}
    @if ($tasks->isNotEmpty())
        <div class="space-y-3">
            @foreach ($tasks as $task)
                <x-card padding="false" class="overflow-hidden" wire:key="task-{{ $task->id }}">
                    <div class="flex items-center gap-3 p-4">
                        <a href="{{ route('tasks.show', $task) }}" wire:navigate class="flex size-9 flex-none items-center justify-center rounded-full border-2 @if($task->status === \App\Enums\TaskStatus::Completed) border-emerald-500 bg-emerald-500 text-white @elseif($task->status === \App\Enums\TaskStatus::InProgress) border-blue-500 bg-blue-500 text-white @elseif($task->status === \App\Enums\TaskStatus::Skipped) border-rose-400 bg-rose-400 text-white @else border-zinc-300 dark:border-zinc-700 @endif">
                            @if($task->status === \App\Enums\TaskStatus::Completed)
                                <flux:icon name="check" class="size-5" variant="solid" />
                            @elseif($task->status === \App\Enums\TaskStatus::InProgress)
                                <flux:icon name="play" class="size-4" variant="solid" />
                            @elseif($task->status === \App\Enums\TaskStatus::Skipped)
                                <flux:icon name="forward" class="size-4" variant="solid" />
                            @endif
                        </a>
                        <a href="{{ route('tasks.show', $task) }}" wire:navigate class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="truncate font-medium @if($task->status === \App\Enums\TaskStatus::Completed) text-zinc-400 line-through dark:text-zinc-500 @endif">{{ $task->title }}</p>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ \App\Concerns\HasColorBadge::badgeClasses($task->priority->color()) }}">{{ $task->priority->label() }}</span>
                                @if($task->status !== \App\Enums\TaskStatus::Pending && $task->status !== \App\Enums\TaskStatus::Completed)
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-medium {{ $task->badgeClass() }}">{{ $task->status->label() }}</span>
                                @endif
                            </div>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <p class="truncate">
                                    @if($task->category) {{ $task->category->name }} · @endif
                                    @if(intdiv($task->planned_minutes,60)) {{ intdiv($task->planned_minutes,60) }}h @endif @if($task->planned_minutes%60) {{ $task->planned_minutes%60 }}m @endif
                                </p>
                                @if($task->progress_percentage > 0)
                                    <span class="text-brand-600 dark:text-brand-400">{{ $task->progress_percentage }}%</span>
                                @endif
                            </div>
                        </a>
                        <div class="flex flex-none items-center gap-1">
                            <button type="button" wire:click="openEdit({{ $task->id }})" class="rounded-lg p-2 text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ __('Edit') }}">
                                <flux:icon name="pencil" class="size-5" />
                            </button>
                            <button type="button" wire:click="deleteTask({{ $task->id }})" wire:confirm="{{ __('Remove this task?') }}" class="rounded-lg p-2 text-rose-500 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" aria-label="{{ __('Delete') }}">
                                <flux:icon name="trash" class="size-5" />
                            </button>
                        </div>
                    </div>
                    @if($task->progress_percentage > 0)
                        <div class="h-1 bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full {{ $task->status === \App\Enums\TaskStatus::Completed ? 'bg-emerald-500' : 'bg-brand-500' }}" style="width: {{ $task->progress_percentage }}%"></div>
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>
    @elseif (! $addingToPlanId && ! $editingTaskId)
        <div class="flex flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-zinc-300 bg-white p-10 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="inbox" class="size-8 text-zinc-300 dark:text-zinc-700" />
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No tasks yet. Add your first task above.') }}</p>
        </div>
    @endif
</div>