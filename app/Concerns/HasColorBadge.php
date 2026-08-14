<?php

namespace App\Concerns;

trait HasColorBadge
{
    /**
     * Map a semantic color name to Tailwind badge classes.
     */
    public static function badgeClasses(string $color): string
    {
        return match ($color) {
            'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400',
            'emerald' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400',
            'amber' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400',
            'rose' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-400',
            'violet' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/15 dark:text-violet-400',
            'brand' => 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-400',
            default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-500/15 dark:text-zinc-400',
        };
    }

    public function badgeClass(): string
    {
        return self::badgeClasses($this->color());
    }
}
