<?php

namespace App\Enums;

enum TimerStatus: string
{
    case Running = 'running';
    case Paused = 'paused';
    case Stopped = 'stopped';

    public function label(): string
    {
        return match ($this) {
            self::Running => __('Running'),
            self::Paused => __('Paused'),
            self::Stopped => __('Stopped'),
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Running, self::Paused], true);
    }
}
