<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Paused = 'paused';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => __('Active'),
            self::Completed => __('Completed'),
            self::Paused => __('Paused'),
            self::Archived => __('Archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'blue',
            self::Completed => 'emerald',
            self::Paused => 'amber',
            self::Archived => 'zinc',
        };
    }
}
