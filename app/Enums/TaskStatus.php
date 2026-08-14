<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Skipped = 'skipped';
    case PartiallyCompleted = 'partially_completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('Pending'),
            self::InProgress => __('In Progress'),
            self::Completed => __('Completed'),
            self::Skipped => __('Skipped'),
            self::PartiallyCompleted => __('Partially Completed'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::InProgress => 'blue',
            self::Completed => 'emerald',
            self::Skipped => 'rose',
            self::PartiallyCompleted => 'amber',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Skipped], true);
    }
}
