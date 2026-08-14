<x-app-shell :title="__('Home')" active="home">
    @php
        $user = auth()->user();
        $hour = now()->hour;
        $greeting = $hour < 12 ? __('Good Morning') : ($hour < 17 ? __('Good Afternoon') : __('Good Evening'));
        $today = now()->translatedFormat('l, F j');
    @endphp

    {{-- Greeting --}}
    <div class="mb-5">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $greeting }}, {{ $user->name }} 👋</p>
        <h1 class="text-2xl font-bold tracking-tight">{{ $today }}</h1>
    </div>

    {{-- Daily score --}}
    <x-card padding="false" class="overflow-hidden">
        <div class="flex flex-col items-center gap-3 p-5">
            <x-progress-ring :value="0" :size="180" :stroke="14" label="{{ __('Productivity Score') }}" sublabel="{{ __('No tasks yet today') }}" />
            <div class="mt-2 grid w-full grid-cols-3 divide-x divide-zinc-200/70 dark:divide-zinc-800/70">
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold">0</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Completed') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold">0</p>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('In Progress') }}</p>
                </div>
                <div class="px-2 py-2 text-center">
                    <p class="text-lg font-bold">0</p>
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
        <x-card><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total Tasks') }}</p><p class="text-2xl font-bold">0</p></x-card>
        <x-card><p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Completion') }}</p><p class="text-2xl font-bold">0%</p></x-card>
    </div>
    <x-card class="mt-3">
        <div class="flex items-center justify-between text-sm">
            <span class="text-zinc-600 dark:text-zinc-300">{{ __('Planned Time') }}</span>
            <span class="font-semibold">0h</span>
        </div>
        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-brand-500" style="width:0%"></div>
        </div>
        <div class="mt-1 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
            <span>{{ __('Completed Time') }}: 0h</span>
            <span>0 / 0h</span>
        </div>
    </x-card>

    {{-- Category progress --}}
    <div class="mt-4 mb-2 flex items-center justify-between">
        <h2 class="text-base font-semibold">{{ __('Category Progress') }}</h2>
    </div>
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
        <a href="{{ route('today') }}" wire:navigate class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="play" class="size-5 text-emerald-500" />
            {{ __('Start Timer') }}
        </a>
        <a href="#" class="flex items-center gap-2 rounded-2xl border border-zinc-200/80 bg-white p-4 text-sm font-medium shadow-sm transition active:scale-95 dark:border-zinc-800/80 dark:bg-zinc-900">
            <flux:icon name="moon" class="size-5 text-violet-500" />
            {{ __('End My Day') }}
        </a>
    </div>

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
</x-app-shell>