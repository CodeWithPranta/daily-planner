@props([
    'as' => 'div',
    'padding' => true,
])

<{{ $as }} {{ $attributes->merge(['class' => 'rounded-2xl border border-zinc-200/80 bg-white shadow-sm dark:border-zinc-800/80 dark:bg-zinc-900']) }}>
    @if($padding)
        <div class="p-4">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
</{{ $as }}>