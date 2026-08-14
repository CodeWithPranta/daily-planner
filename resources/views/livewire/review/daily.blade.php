<div>
    {{-- Review header --}}
    <div class="mb-5">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('End of Day Review') }}</p>
        <h1 class="text-2xl font-bold tracking-tight">{{ $dateLabel }}</h1>
    </div>

    {{-- Score overview --}}
    <x-card padding="false" class="overflow-hidden">
        <div class="flex flex-col items-center gap-3 p-5">
            @if ($score->totalCount === 0)
                <x-progress-ring :value="0" :size="160" :stroke="12" label="{{ __('Productivity Score') }}" sublabel="{{ __('No tasks for this day') }}" />
            @else
                <x-progress-ring :value="$score->score" :size="160" :stroke="12" label="{{ __('Productivity Score') }}" />
            @endif
            <div class="mt-2 grid w-full grid-cols-3 divide-x divide-zinc-200/70 dark:divide-zinc-800/70">
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $score->completedCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Done') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $score->inProgressCount + $score->partialCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Partial') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-rose-500 dark:text-rose-400">{{ $score->pendingCount + $score->skippedCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Not Done') }}</p>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Time summary --}}
    @if ($score->totalPlannedMinutes > 0)
        <x-card class="mt-4">
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-300">{{ __('Time Spent') }}</span>
                <span class="font-semibold">{{ intdiv($score->totalActualMinutes, 60) }}h {{ $score->totalActualMinutes % 60 }}m / {{ intdiv($score->totalPlannedMinutes, 60) }}h {{ $score->totalPlannedMinutes % 60 }}m</span>
            </div>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ min(100, $score->totalPlannedMinutes > 0 ? round($score->totalActualMinutes / $score->totalPlannedMinutes * 100) : 0) }}%"></div>
            </div>
        </x-card>
    @endif

    {{-- Task breakdown --}}
    @if ($completedTasks->isNotEmpty())
        <div class="mt-5 mb-2">
            <h2 class="text-base font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Completed') }} ({{ $completedTasks->count() }})</h2>
        </div>
        <div class="space-y-2">
            @foreach ($completedTasks as $task)
                <a href="{{ route('tasks.show', $task) }}" wire:navigate class="block">
                    <x-card class="flex items-center gap-3">
                        <flux:icon name="check-circle" class="size-5 text-emerald-500" variant="solid" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ intdiv($task->actual_minutes, 60) }}h {{ $task->actual_minutes % 60 }}m</p>
                        </div>
                        @if ($task->category)
                            <span class="size-3 rounded-full" style="background-color: {{ $task->category->color }}"></span>
                        @endif
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    @if ($inProgressTasks->isNotEmpty() || $partialTasks->isNotEmpty())
        <div class="mt-5 mb-2">
            <h2 class="text-base font-semibold text-blue-600 dark:text-blue-400">{{ __('In Progress') }}</h2>
        </div>
        <div class="space-y-2">
            @foreach (($inProgressTasks->merge($partialTasks)) as $task)
                <a href="{{ route('tasks.show', $task) }}" wire:navigate class="block">
                    <x-card class="flex items-center gap-3">
                        <flux:icon name="clock" class="size-5 text-blue-500" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                            <div class="mt-1 h-1 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-blue-500" style="width: {{ $task->progress_percentage }}%"></div>
                            </div>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    @if ($pendingTasks->isNotEmpty())
        <div class="mt-5 mb-2">
            <h2 class="text-base font-semibold text-zinc-500 dark:text-zinc-400">{{ __('Still Pending') }}</h2>
        </div>
        <div class="space-y-2">
            @foreach ($pendingTasks as $task)
                <a href="{{ route('tasks.show', $task) }}" wire:navigate class="block">
                    <x-card class="flex items-center gap-3 opacity-60">
                        <flux:icon name="minus" class="size-5 text-zinc-400" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ $task->title }}</p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    @if ($skippedTasks->isNotEmpty())
        <div class="mt-5 mb-2">
            <h2 class="text-base font-semibold text-rose-500 dark:text-rose-400">{{ __('Skipped') }}</h2>
        </div>
        <div class="space-y-2">
            @foreach ($skippedTasks as $task)
                <a href="{{ route('tasks.show', $task) }}" wire:navigate class="block">
                    <x-card class="flex items-center gap-3 opacity-60">
                        <flux:icon name="minus-circle" class="size-5 text-rose-400" />
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate line-through">{{ $task->title }}</p>
                        </div>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Reflection --}}
    @if ($score->totalCount > 0)
        <div class="mt-6 mb-2">
            <h2 class="text-base font-semibold">{{ __('Reflection') }}</h2>
        </div>
        <x-card>
            <flux:textarea
                wire:model="reflection"
                :label="__('How did your day go?')"
                :placeholder="__('What went well? What could you improve tomorrow?')"
                rows="4"
            />
        </x-card>

        <div class="mt-4 flex gap-3">
            <flux:button wire:click="submitReview" variant="primary" class="flex-1">
                {{ __('Save Review') }}
            </flux:button>
            <flux:button wire:click="completeDay" variant="primary" class="flex-1" style="background: var(--color-emerald-600)">
                {{ __('Complete Day') }}
            </flux:button>
        </div>
    @else
        <x-card class="mt-6">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="calendar-days" class="size-5" />
                <p>{{ __('No tasks were planned for this day. Visit the Today page to start planning.') }}</p>
            </div>
            <a href="{{ route('today') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-600 dark:text-brand-400">
                {{ __('Go to Today') }} <flux:icon name="arrow-right" class="size-4" />
            </a>
        </x-card>
    @endif
</div>