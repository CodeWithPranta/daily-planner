@php
    $chartData = json_encode([
        'scoreTrend' => $data->scoreTrend,
        'categoryDistribution' => $data->categoryDistribution,
        'weeklyTimeByCategory' => $data->weeklyTimeByCategory,
    ]);

    $hasScoreData = $data->activeDayCount > 0;
@endphp

<div>
    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('Analytics') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Track your productivity trends and insights.') }}</p>
    </div>

    @php
        $ranges = [
            'today' => __('Today'),
            'week' => __('This Week'),
            'month' => __('This Month'),
            '30days' => __('Last 30 Days'),
        ];
    @endphp

    {{-- Range selector --}}
    <div class="mb-4 flex gap-2 overflow-x-auto no-scrollbar">
        @foreach ($ranges as $key => $label)
            <button type="button" wire:click="setRange('{{ $key }}')"
                class="flex-none rounded-full px-4 py-1.5 text-sm font-medium transition {{ $range === $key ? 'bg-brand-600 text-white' : 'bg-white text-zinc-600 border border-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:border-zinc-800' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Avg Score') }}</p>
            <p class="text-2xl font-bold">{{ $data->averageScore }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Completion') }}</p>
            <p class="text-2xl font-bold">{{ $data->completionPercent }}%</p>
            <p class="text-xs text-zinc-500">{{ $data->totalCompleted }} / {{ $data->totalTasks }}</p>
        </x-card>
        <x-card class="flex items-center gap-3">
            <flux:icon name="fire" class="size-7 text-orange-500" variant="solid" />
            <div>
                <p class="text-2xl font-bold">{{ $data->currentStreak }}</p>
                <p class="text-xs text-zinc-500">{{ __('Current Streak') }}</p>
            </div>
        </x-card>
        <x-card class="flex items-center gap-3">
            <flux:icon name="trophy" class="size-7 text-amber-500" variant="solid" />
            <div>
                <p class="text-2xl font-bold">{{ $data->longestStreak }}</p>
                <p class="text-xs text-zinc-500">{{ __('Longest Streak') }}</p>
            </div>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Time Logged') }}</p>
            <p class="text-2xl font-bold">{{ $data->totalActualHours() }}h</p>
            <p class="text-xs text-zinc-500">{{ $data->timeUtilizationPercent() }}% utilized</p>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Consistency') }}</p>
            <p class="text-2xl font-bold">{{ $data->consistencyPercent() }}%</p>
            <p class="text-xs text-zinc-500">{{ $data->activeDayCount }} / {{ $data->totalDays }} {{ __('days') }}</p>
        </x-card>
    </div>

    {{-- Score trend chart --}}
    @if ($hasScoreData)
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">{{ __('Productivity Score Trend') }}</h2>
            <x-card padding="false" class="overflow-hidden">
                <div class="p-4">
                    <canvas id="scoreTrendChart" height="180"></canvas>
                </div>
            </x-card>
        </div>
    @else
        <x-card class="mb-4">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="chart-pie" class="size-5" />
                <p>{{ __('No data for this range yet. Complete some tasks and run a review!') }}</p>
            </div>
        </x-card>
    @endif

    {{-- Category distribution doughnut --}}
    @if (!empty($data->categoryDistribution))
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">{{ __('Time by Category') }}</h2>
            <x-card padding="false" class="overflow-hidden">
                <div class="p-4">
                    <canvas id="categoryChart" height="200"></canvas>
                </div>
            </x-card>
        </div>
    @else
        <x-card class="mb-4">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="chart-pie" class="size-5" />
                <p>{{ __('No time logged yet. Start tasks with a timer to see category breakdown.') }}</p>
            </div>
        </x-card>
    @endif

    {{-- Time by category stacked bar --}}
    @if (!empty($data->weeklyTimeByCategory['datasets']))
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">{{ __('Time Trend by Category') }}</h2>
            <x-card padding="false" class="overflow-hidden">
                <div class="p-4">
                    <canvas id="weeklyCategoryChart" height="200"></canvas>
                </div>
            </x-card>
        </div>
    @else
        <x-card class="mb-4">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="chart-pie" class="size-5" />
                <p>{{ __('No time trend data yet. Log time on tasks to see the breakdown.') }}</p>
            </div>
        </x-card>
    @endif

    {{-- Active goals --}}
    @if (!empty($data->goals))
        <div class="mb-4">
            <h2 class="mb-2 text-base font-semibold">{{ __('Active Goals') }}</h2>
            <div class="space-y-3">
                @foreach ($data->goals as $goal)
                    <x-card>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                @if ($goal['category_color'])
                                    <span class="size-3 rounded-full" style="background-color: {{ $goal['category_color'] }}"></span>
                                @endif
                                <span class="text-sm font-medium">{{ $goal['title'] }}</span>
                            </div>
                            <span class="text-sm font-semibold">{{ $goal['progress'] }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $goal['progress'] }}%"></div>
                        </div>
                        @if ($goal['target_date'])
                            <p class="mt-1 text-xs text-zinc-400">{{ __('Target') }}: {{ $goal['target_date']->translatedFormat('M j, Y') }}</p>
                        @endif
                    </x-card>
                @endforeach
            </div>
            <a href="{{ route('goals.index') }}" wire:navigate class="mt-3 inline-flex items-center gap-1 text-sm font-medium text-brand-600 dark:text-brand-400">
                {{ __('View all goals') }} <flux:icon name="arrow-right" class="size-4" />
            </a>
        </div>
    @endif

    {{-- Links to reports --}}
    @if ($hasScoreData)
        <div class="mt-6 grid grid-cols-2 gap-3">
            <a href="{{ route('reports.weekly') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
                <flux:icon name="calendar-days" class="size-5 text-brand-600 dark:text-brand-400" />
                {{ __('Weekly Report') }}
            </a>
            <a href="{{ route('reports.monthly') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
                <flux:icon name="calendar" class="size-5 text-brand-600 dark:text-brand-400" />
                {{ __('Monthly Report') }}
            </a>
        </div>
    @endif

    {{-- Chart data (raw JSON, read by JS in app.js) --}}
    <script id="analytics-data" type="application/json">{!! $chartData !!}</script>
</div>