<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => __('Low'),
            self::Medium => __('Medium'),
            self::High => __('High'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'zinc',
            self::Medium => 'blue',
            self::High => 'rose',
        };
    }

    public function weight(): float
    {
        return match ($this) {
            self::Low => 0.7,
            self::Medium => 1.0,
            self::High => 1.5,
        };
    }
}
