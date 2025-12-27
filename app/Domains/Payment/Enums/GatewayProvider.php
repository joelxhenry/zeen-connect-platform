<?php

namespace App\Domains\Payment\Enums;

enum GatewayProvider: string
{
    case WIPAY = 'wipay';
    case FYGARO = 'fygaro';
    case POWERTRANZ = 'powertranz';

    public function label(): string
    {
        return match ($this) {
            self::WIPAY => 'WiPay',
            self::FYGARO => 'Fygaro',
            self::POWERTRANZ => 'PowerTranz',
        };
    }

    public function supportsSplit(): bool
    {
        return match ($this) {
            self::WIPAY => true,
            self::FYGARO => true,
            self::POWERTRANZ => false,
        };
    }

    public function supportsEscrow(): bool
    {
        return true; // All providers support escrow
    }

    public function configKey(): string
    {
        return "services.{$this->value}";
    }

    /**
     * Get all providers as options for forms.
     *
     * @return array<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $provider) => [
            'value' => $provider->value,
            'label' => $provider->label(),
        ], self::cases());
    }
}
