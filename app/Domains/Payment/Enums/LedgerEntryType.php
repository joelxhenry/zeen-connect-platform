<?php

namespace App\Domains\Payment\Enums;

enum LedgerEntryType: string
{
    case CREDIT = 'credit';
    case DEBIT = 'debit';
    case HOLD = 'hold';
    case RELEASE = 'release';

    public function label(): string
    {
        return match ($this) {
            self::CREDIT => 'Credit',
            self::DEBIT => 'Debit',
            self::HOLD => 'Hold',
            self::RELEASE => 'Release',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CREDIT => 'Funds added to provider balance',
            self::DEBIT => 'Funds removed from provider balance',
            self::HOLD => 'Funds held pending release',
            self::RELEASE => 'Held funds released',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CREDIT => 'pi pi-arrow-down',
            self::DEBIT => 'pi pi-arrow-up',
            self::HOLD => 'pi pi-lock',
            self::RELEASE => 'pi pi-unlock',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CREDIT => 'success',
            self::DEBIT => 'danger',
            self::HOLD => 'warning',
            self::RELEASE => 'info',
        };
    }

    /**
     * Check if this entry type increases available balance.
     */
    public function increasesBalance(): bool
    {
        return $this === self::CREDIT || $this === self::RELEASE;
    }

    /**
     * Check if this entry type decreases available balance.
     */
    public function decreasesBalance(): bool
    {
        return $this === self::DEBIT || $this === self::HOLD;
    }
}
