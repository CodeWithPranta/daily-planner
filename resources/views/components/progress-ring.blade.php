@props([
    'value' => 0,
    'size' => 180,
    'stroke' => 14,
    'label' => null,
    'sublabel' => null,
])

@php
    $value = max(0, min(100, (float) $value));
    $radius = ($size - $stroke) / 2;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - ($value / 100) * $circumference;
    $colorClass = $value >= 75 ? 'text-emerald-500' : ($value >= 50 ? 'text-brand-500' : ($value >= 25 ? 'text-amber-500' : 'text-rose-500'));
@endphp

<div class="relative inline-flex items-center justify-center" style="width:{{ $size }}px;height:{{ $size }}px;">
    <svg width="{{ $size }}" height="{{ $size }}" class="-rotate-90" viewBox="0 0 {{ $size }} {{ $size }}">
        <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
                stroke="currentColor" stroke-width="{{ $stroke }}"
                class="text-zinc-200 dark:text-zinc-800" fill="none" />
        <circle cx="{{ $size/2 }}" cy="{{ $size/2 }}" r="{{ $radius }}"
                stroke="currentColor" stroke-width="{{ $stroke }}"
                class="{{ $colorClass }} transition-[stroke-dashoffset] duration-700 ease-out" fill="none"
                stroke-linecap="round"
                stroke-dasharray="{{ $circumference }}"
                stroke-dashoffset="{{ $offset }}" />
    </svg>
    <div class="absolute flex flex-col items-center justify-center text-center">
        <span class="text-4xl font-bold tracking-tight">{{ round($value) }}<span class="text-xl">%</span></span>
        @isset($label)
            <span class="mt-1 text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $label }}</span>
        @endisset
        @isset($sublabel)
            <span class="text-[11px] text-zinc-400 dark:text-zinc-500">{{ $sublabel }}</span>
        @endisset
    </div>
</div>