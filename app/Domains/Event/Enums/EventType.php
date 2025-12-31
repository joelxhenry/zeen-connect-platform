<?php

namespace App\Domains\Event\Enums;

enum EventType: string
{
    case ONE_TIME = 'one_time';
    case RECURRING = 'recurring';

    public function label(): string
    {
        return match ($this) {
            self::ONE_TIME => 'One-time',
            self::RECURRING => 'Recurring',
        };
    }
}
