<?php

namespace App\Domains\User\Enums;

enum UserRole: string
{
    case Client = 'client';
    case Provider = 'provider';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Client => 'Client',
            self::Provider => 'Service Provider',
            self::Admin => 'Administrator',
        };
    }

    public function isClient(): bool
    {
        return $this === self::Client;
    }

    public function isProvider(): bool
    {
        return $this === self::Provider;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
