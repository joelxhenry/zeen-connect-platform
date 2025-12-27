<?php

namespace App\Domains\Payment\Enums;

enum GatewayType: string
{
    case SPLIT = 'split';
    case ESCROW = 'escrow';

    public function label(): string
    {
        return match ($this) {
            self::SPLIT => 'Direct Split',
            self::ESCROW => 'Escrow',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SPLIT => 'Payments are split instantly between platform and provider',
            self::ESCROW => 'Platform collects full payment, provider receives payout later',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::SPLIT => 'pi pi-share-alt',
            self::ESCROW => 'pi pi-wallet',
        };
    }
}
