<?php

namespace App\Domains\Event\Enums;

enum EventLocationType: string
{
    case IN_PERSON = 'in_person';
    case VIRTUAL = 'virtual';

    public function label(): string
    {
        return match ($this) {
            self::IN_PERSON => 'In Person',
            self::VIRTUAL => 'Virtual',
        };
    }
}
