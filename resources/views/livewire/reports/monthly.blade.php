<div>
    {{-- Header --}}
    <div class="mb-5">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Monthly Report') }}</p>
        <h1 class="text-2xl font-bold tracking-tight">{{ $summary->label() }}</h1>
    </div>

    {{-- Streak cards --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <x-card class="flex items-center gap-3">
            <flux:icon name="fire" class="size-8 text-orange-500" variant="solid" />
            <div>
                <p class="text-2xl font-bold">{{ $currentStreak }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Current Streak') }}</p>
            </div>
        </x-card>
        <x-card class="flex items-center gap-3">
            <flux:icon name="trophy" class="size-8 text-amber-500" variant="solid" />
            <div>
                <p class="text-2xl font-bold">{{ $longestStreak }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Longest Streak') }}</p>
            </div>
        </x-card>
    </div>

    {{-- Summary stats --}}
    <div class="grid grid-cols-2 gap-3 mb-4">
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Avg Score') }}</p>
            <p class="text-2xl font-bold">{{ $summary->averageScore }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Active Days') }}</p>
            <p class="text-2xl font-bold">{{ $summary->activeDayCount }}<span class="text-sm text-zinc-400">/{{ $summary->totalDaysInMonth }}</span></p>
            <p class="text-xs text-zinc-500">{{ $summary->consistencyPercent() }}% {{ __('consistency') }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Completed') }}</p>
            <p class="text-2xl font-bold">{{ $summary->totalCompleted }}</p>
            <p class="text-xs text-zinc-500">{{ __('of :n tasks', ['n' => $summary->totalTasks]) }} ({{ $summary->completionPercent() }}%)</p>
        </x-card>
        <x-card>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Time Logged') }}</p>
            <p class="text-2xl font-bold">{{ intdiv($summary->totalActualMinutes, 60) }}h {{ $summary->totalActualMinutes % 60 }}m</p>
            <p class="text-xs text-zinc-500">{{ $summary->timeUtilizationPercent() }}% utilized</p>
        </x-card>
    </div>

    {{-- Best day --}}
    @if ($summary->bestDay)
        <x-card class="mb-4">
            <div class="flex items-center gap-3">
                <flux:icon name="star" class="size-8 text-amber-500" variant="solid" />
                <div class="flex-1">
                    <p class="text-sm font-semibold">{{ __('Best Day') }}</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ \Carbon\Carbon::parse($summary->bestDay->date)->translatedFormat('l, F j') }} —
                        {{ __('Score') }}: {{ round($summary->bestDay->score) }} |
                        {{ $summary->bestDay->completed_tasks }} {{ __('completed') }}
                    </p>
                </div>
            </div>
        </x-card>
    @endif

    {{-- Daily breakdown chart --}}
    @if ($summary->scores->isNotEmpty())
        <div class="mt-4 mb-2">
            <h2 class="text-base font-semibold">{{ __('Daily Scores') }}</h2>
        </div>
        <x-card>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @php $maxScore = $summary->scores->max('score') ?: 100; @endphp
                @foreach ($summary->scores as $dayScore)
                    @php
                        $barWidth = $maxScore > 0 ? ($dayScore->score / $maxScore) * 100 : 0;
                        $dayLabel = \Carbon\Carbon::parse($dayScore->date)->translatedFormat('j M');
                        $isToday = \Carbon\Carbon::parse($dayScore->date)->isToday();
                        $score = $dayScore->score;
                    @endphp
                    <div class="flex items-center gap-3">
                        <span class="w-14 text-xs {{ $isToday ? 'font-bold text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $dayLabel }}</span>
                        <div class="flex-1 h-6 overflow-hidden rounded-lg bg-zinc-200 dark:bg-zinc-800">
                            <div class="h-full rounded-lg transition-all {{ $score >= 70 ? 'bg-emerald-500' : ($score >= 40 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $barWidth }}%"></div>
                        </div>
                        <span class="w-10 text-right text-xs font-semibold">{{ $dayScore->total_tasks > 0 ? round($score) : '—' }}</span>
                    </div>
                @endforeach
            </div>
        </x-card>
    @else
        <x-card class="mt-4">
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <flux:icon name="chart-pie" class="size-5" />
                <p>{{ __('No data for this month yet. Complete some tasks and run an end-of-day review!') }}</p>
            </div>
        </x-card>
    @endif

    {{-- Navigation --}}
    <div class="mt-6 flex items-center justify-between">
        <a href="{{ route('reports.monthly', ['monthOffset' => ($monthOffset ?? 0) + 1]) }}" wire:navigate class="flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-brand-600">
            <flux:icon name="chevron-left" class="size-4" /> {{ __('Previous') }}
        </a>
        @if (($monthOffset ?? 0) > 0)
            <a href="{{ route('reports.monthly', ['monthOffset' => ($monthOffset ?? 0) - 1]) }}" wire:navigate class="flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-brand-600">
                {{ __('Next') }} <flux:icon name="chevron-right" class="size-4" />
            </a>
        @endif
    </div>

    <!-- Hidden trend data for potential Chart.js -->
    <script type="application/json" id="monthly-trend-data" hidden>{{ json_encode($trend) }}</script>
</div>