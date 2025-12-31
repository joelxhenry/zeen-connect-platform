<?php

namespace App\Domains\Service\Enums;

enum CategoryType: string
{
    case SERVICE = 'service';
    case EVENT = 'event';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::SERVICE => 'Service',
            self::EVENT => 'Event',
        };
    }

    /**
     * Get all values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all cases as options for dropdowns.
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
