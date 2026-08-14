@props([
    'active' => null,
])

@php
    $items = [
        'home'      => ['label' => __('Home'),      'icon' => 'home',            'href' => route('dashboard')],
        'today'     => ['label' => __('Today'),     'icon' => 'clipboard-document-list', 'href' => route('today')],
        'analytics' => ['label' => __('Analytics'), 'icon' => 'chart-pie',       'href' => route('analytics')],
        'profile'   => ['label' => __('Profile'),   'icon' => 'user',            'href' => route('profile.edit')],
    ];
    // Order on screen: Home, Today, [Add Task FAB], Analytics, Profile
    $left  = ['home', 'today'];
    $right = ['analytics', 'profile'];
@endphp

<nav class="pt-safe fixed inset-x-0 bottom-0 z-40 mx-auto h-nav w-full max-w-md md:max-w-lg">
    <div class="relative mx-auto flex h-16 max-w-md items-end justify-around border-t border-zinc-200/80 bg-zinc-50/95 px-2 backdrop-blur-lg dark:border-zinc-800/80 dark:bg-zinc-950/95">
        @foreach ($left as $key)
            @php $item = $items[$key]; @endphp
            <a href="{{ $item['href'] }}" wire:navigate
               class="flex flex-1 flex-col items-center justify-center gap-0.5 pb-1 pt-2 text-[11px] font-medium transition {{ $active === $key ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}"
               aria-current="{{ $active === $key ? 'page' : 'false' }}">
                <flux:icon name="{{ $item['icon'] }}" class="size-6" :variant="$active === $key ? 'solid' : 'outline'" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach

        {{-- Center FAB --}}
        <a href="{{ route('today', ['add' => 1]) }}" wire:navigate
           class="relative -top-5 flex size-14 flex-none items-center justify-center rounded-full bg-brand-600 text-white shadow-lg shadow-brand-600/40 ring-4 ring-zinc-50 dark:ring-zinc-950 transition active:scale-95"
           aria-label="{{ __('Add Task') }}">
            <flux:icon name="plus" class="size-7" variant="solid" />
        </a>

        @foreach ($right as $key)
            @php $item = $items[$key]; @endphp
            <a href="{{ $item['href'] }}" wire:navigate
               class="flex flex-1 flex-col items-center justify-center gap-0.5 pb-1 pt-2 text-[11px] font-medium transition {{ $active === $key ? 'text-brand-600 dark:text-brand-400' : 'text-zinc-500 dark:text-zinc-400' }}"
               aria-current="{{ $active === $key ? 'page' : 'false' }}">
                <flux:icon name="{{ $item['icon'] }}" class="size-6" :variant="$active === $key ? 'solid' : 'outline'" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </div>
</nav>