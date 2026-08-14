<?php

namespace App\Enums;

enum DailyPlanStatus: string
{
    case Draft = 'draft';
    case Planned = 'planned';
    case Reviewed = 'reviewed';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => __('Draft'),
            self::Planned => __('Planned'),
            self::Reviewed => __('Reviewed'),
            self::Completed => __('Completed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Planned => 'blue',
            self::Reviewed => 'violet',
            self::Completed => 'emerald',
        };
    }
}
