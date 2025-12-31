<?php

namespace App\Domains\Auth\Enums;

enum SocialProvider: string
{
    case Google = 'google';
    case Apple = 'apple';

    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Apple => 'Apple',
        };
    }

    /**
     * Get all valid provider values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a provider string is valid.
     */
    public static function isValid(string $provider): bool
    {
        return in_array($provider, self::values());
    }
}
