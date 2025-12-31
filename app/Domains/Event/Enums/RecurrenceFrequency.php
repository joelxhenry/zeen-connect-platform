<?php

namespace App\Domains\Event\Enums;

enum RecurrenceFrequency: string
{
    case WEEKLY = 'weekly';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY => 'Weekly',
        };
    }
}
