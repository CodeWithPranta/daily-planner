<x-app-shell :title="__('Analytics')" active="analytics">
    <h1 class="mb-4 text-2xl font-bold tracking-tight">{{ __('Analytics') }}</h1>

    {{-- Range selector (static chips) --}}
    <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 pb-1">
        @php
            $ranges = [__('Today'), __('This Week'), __('This Month'), __('Last 30 Days'), __('Custom')];
        @endphp
        @foreach ($ranges as $i => $range)
            <button type="button"
                class="{{ $i === 0 ? 'bg-brand-600 text-white' : 'bg-white text-zinc-600 border border-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:border-zinc-800' }} whitespace-nowrap rounded-full px-3.5 py-1.5 text-sm font-medium transition">
                {{ $range }}
            </button>
        @endforeach
    </div>

    {{-- Score summary --}}
    <x-card>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Productivity Score') }}</p>
        <div class="mt-1 flex items-end gap-2">
            <span class="text-3xl font-bold">—</span>
            <span class="pb-1 text-xs text-zinc-400">/ 100%</span>
        </div>
        <div class="mt-4 flex h-32 items-end justify-between gap-2">
            @foreach (range(1, 7) as $_)
                <div class="w-full rounded-t bg-zinc-200 dark:bg-zinc-800" style="height:{{ rand(20, 90) }}%"></div>
            @endforeach
        </div>
    </x-card>

    {{-- Working hours placeholder --}}
    <x-card class="mt-3">
        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Working Hours') }}</p>
        <p class="mt-1 text-2xl font-bold">0h</p>
        <p class="mt-2 text-center text-xs text-zinc-400 dark:text-zinc-600">{{ __('Charts arrive with data in the next build phase.') }}</p>
    </x-card>

    {{-- Category distribution placeholder --}}
    <div class="mt-3 mb-2"><h2 class="text-base font-semibold">{{ __('Category Distribution') }}</h2></div>
    <x-card padding="false">
        <div class="p-4 text-sm text-zinc-500 dark:text-zinc-400">
            <div class="flex items-center gap-2">
                <flux:icon name="chart-pie" class="size-5 text-zinc-400" />
                <p>{{ __('No tracked time yet. Start working on tasks to see your distribution.') }}</p>
            </div>
        </div>
    </x-card>

    {{-- Streak --}}
    <div class="mt-3 mb-2"><h2 class="text-base font-semibold">{{ __('Consistency') }}</h2></div>
    <x-card>
        <div class="flex items-center gap-3">
            <span class="text-3xl">🔥</span>
            <div>
                <p class="text-lg font-bold">0 {{ __('day streak') }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Plan and complete tasks to build your streak.') }}</p>
            </div>
        </div>
    </x-card>
</x-app-shell>