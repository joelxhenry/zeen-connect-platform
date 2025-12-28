<?php

namespace App\Domains\Payment\Enums;

enum GatewayProvider: string
{
    case WIPAY = 'wipay';

    public function label(): string
    {
        return 'WiPay';
    }

    public function supportsSplit(): bool
    {
        return true;
    }

    public function supportsEscrow(): bool
    {
        return true;
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
