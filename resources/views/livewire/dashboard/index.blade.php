<div>
    {{-- Greeting --}}
    <div class="mb-5">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $greeting }}, {{ $userName }} 👋</p>
        <h1 class="text-2xl font-bold tracking-tight">{{ $todayLabel }}</h1>
    </div>

    {{-- Daily score --}}
    <x-card padding="false" class="overflow-hidden">
        <div class="flex flex-col items-center gap-3 p-5">
            @if ($score->totalCount === 0)
                <x-progress-ring :value="0" :size="180" :stroke="14" label="{{ __('Productivity Score') }}" sublabel="{{ __('No tasks yet today') }}" />
            @else
                <x-progress-ring :value="$score->score" :size="180" :stroke="14" label="{{ __('Productivity Score') }}" />
            @endif
            <div class="mt-2 grid w-full grid-cols-3 divide-x divide-zinc-200/70 dark:divide-zinc-800/70">
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ $score->completedCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Completed') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ $score->inProgressCount + $score->partialCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('In Progress') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold text-zinc-500 dark:text-zinc-400">{{ $score->pendingCount + $score->skippedCount }}</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Pending') }}</p>
                </div>
            </div>
        </div>
    </x-card>

    {{-- Today's progress summary --}}
    <div class="mt-4 mb-2 flex items-center justify-between">
        <h2 class="text-base font-semibold">{{ __("Today's Progress") }}</h2>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <x-card><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Tasks') }}</p><p class="text-2xl font-bold">{{ $score->totalCount }}</p></x-card>
        <x-card><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Completion') }}</p><p class="text-2xl font-bold">{{ $completionPercent }}%</p></x-card>
    </div>
    <x-card class="mt-3">
        <div class="flex items-center justify-between text-sm">
            <span class="text-zinc-600 dark:text-zinc-300">{{ __('Planned Time') }}</span>
            <span class="font-semibold">@if($plannedH) {{ $plannedH }}h @endif @if($plannedM) {{ $plannedM }}m @endif</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $timePercent }}%"></div>
        </div>
        <div class="mt-1 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
            <span>{{ __('Completed Time') }}: @if($actualH) {{ $actualH }}h @endif @if($actualM) {{ $actualM }}m @endif</span>
            <span>{{ $timePercent }}%</span>
        </div>
    </x-card>

    {{-- Category progress --}}
    <div class="mt-4 mb-2 flex items-center justify-between">
        <h2 class="text-base font-semibold">{{ __('Category Progress') }}</h2>
    </div>
    @if (empty($score->categoryBreakdown))
        <x-card padding="false">
            <div class="p-4 text-sm text-zinc-500 dark:text-zinc-400">
                <div class="flex items-center gap-2">
                    <flux:icon name="folder-plus" class="size-5 text-zinc-400" />
                    <p>{{ __('Create categories to track time by area of life.') }}</p>
                </div>
                <a href="{{ route('today') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-600 dark:text-brand-400">
                    {{ __('Plan your day') }} <flux:icon name="arrow-right" class="size-4" />
                </a>
            </div>
        </x-card>
    @else
        <div class="space-y-3">
            @foreach ($score->categoryBreakdown as $cat)
                @php
                    $catPlannedH = intdiv($cat['planned'], 60);
                    $catPlannedM = $cat['planned'] % 60;
                    $catActualH = intdiv($cat['actual'], 60);
                    $catActualM = $cat['actual'] % 60;
                @endphp
                <x-card>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            @if ($cat['color'])
                                <span class="size-3 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                            @endif
                            <span class="text-sm font-medium">{{ $cat['name'] ?? __('Uncategorized') }}</span>
                        </div>
                        <span class="text-sm font-semibold">{{ $cat['completion'] }}%</span>
                    </div>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                        <div class="h-full rounded-full" style="width: {{ $cat['completion'] }}%; background-color: {{ $cat['color'] ?? '#6366f1' }}"></div>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        @if($catActualH) {{ $catActualH }}h @endif @if($catActualM) {{ $catActualM }}m @endif / @if($catPlannedH) {{ $catPlannedH }}h @endif @if($catPlannedM) {{ $catPlannedM }}m @endif
                    </p>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Quick actions --}}
    <div class="mt-4 mb-2 flex items-center justify-between">
        <h2 class="text-base font-semibold">{{ __('Quick Actions') }}</h2>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('today') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="calendar-days" class="size-5 text-brand-600 dark:text-brand-400" />
            {{ __('Plan My Day') }}
        </a>
        <a href="{{ route('today', ['add' => 1]) }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="plus-circle" class="size-5 text-brand-600 dark:text-brand-400" />
            {{ __('Add Task') }}
        </a>
        @if ($startableTask)
            <a href="{{ route('tasks.show', $startableTask) }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
                <flux:icon name="play" class="size-5 text-emerald-500" />
                {{ __('Start Timer') }}
            </a>
        @else
            <a href="{{ route('today', ['add' => 1]) }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
                <flux:icon name="plus-circle" class="size-5 text-zinc-400" />
                {{ __('Add Task First') }}
            </a>
        @endif
        <a href="{{ route('review.daily') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="moon" class="size-5 text-violet-500" />
            {{ __('End My Day') }}
        </a>
    </div>

    {{-- Quick Create --}}
    <div class="mt-4 mb-2 flex items-center justify-between">
        <h2 class="text-base font-semibold">{{ __('Quick Create') }}</h2>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <button type="button" wire:click="createCategory" class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="folder-plus" class="size-5 text-brand-600 dark:text-brand-400" />
            {{ __('New Category') }}
        </button>
        <button type="button" wire:click="createGoal" class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="rocket-launch" class="size-5 text-violet-500" />
            {{ __('New Goal') }}
        </button>
    </div>

    {{-- Quick Category form --}}
    @if ($showCategoryForm)
    <x-card class="mt-3">
        <form wire:submit="saveCategory" class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">{{ __('New Category') }}</h3>
                <button type="button" wire:click="cancelCategoryForm" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
            <flux:input wire:model="catName" :label="__('Name')" placeholder="{{ __('e.g. BCS Study') }}" required autofocus />
            <div class="grid grid-2 gap-3">
                <flux:select wire:model="catIcon" :label="__('Icon')">
                    @foreach ($iconOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <div class="grid gap-1.5">
                    <flux:label>{{ __('Color') }}</flux:label>
                    <div class="flex flex-wrap gap-2 pt-0.5">
                        @foreach ($colorOptions as $hex => $name)
                            <button type="button" wire:click="$set('catColor', '{{ $hex }}')"
                                class="size-8 rounded-full ring-offset-2 ring-offset-white transition dark:ring-offset-zinc-900 {{ $catColor === $hex ? 'ring-2 ring-zinc-900 dark:ring-white' : '' }}"
                                style="background-color: {{ $hex }}" aria-label="{{ $name }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-1">
                <flux:button type="button" variant="ghost" wire:click="cancelCategoryForm">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </x-card>
    @endif

    {{-- Quick Goal form --}}
    @if ($showGoalForm)
    <x-card class="mt-3">
        <form wire:submit="saveGoal" class="space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold">{{ __('New Goal') }}</h3>
                <button type="button" wire:click="cancelGoalForm" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <flux:icon name="x-mark" class="size-4" />
                </button>
            </div>
            <flux:input wire:model="goalTitle" :label="__('Title')" placeholder="{{ __('e.g. Become a Laravel Expert') }}" required autofocus />
            <flux:select wire:model="goalCategoryId" :label="__('Category')">
                <option value="">{{ __('None') }}</option>
                @foreach ($categories as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </flux:select>
            <flux:input wire:model="goalTargetValue" :label="__('Target value')" type="number" step="0.01" min="0" placeholder="100" />
            <div class="flex justify-end gap-2 pt-1">
                <flux:button type="button" variant="ghost" wire:click="cancelGoalForm">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
            </div>
        </form>
    </x-card>
    @endif

    {{-- Manage --}}
    <div class="mt-4 mb-2"><h2 class="text-base font-semibold">{{ __('Manage') }}</h2></div>
    <div class="grid grid-cols-2 gap-3">
        <a href="{{ route('categories.index') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="folder" class="size-5 text-amber-500" />
            {{ __('Categories') }}
        </a>
        <a href="{{ route('goals.index') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="rocket-launch" class="size-5 text-violet-500" />
            {{ __('Goals') }}
        </a>
    </div>
</div>