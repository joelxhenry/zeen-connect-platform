<?php

namespace App\Domains\Payment\Enums;

enum ScheduledPayoutStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'pi pi-clock',
            self::PROCESSING => 'pi pi-spin pi-spinner',
            self::COMPLETED => 'pi pi-check-circle',
            self::FAILED => 'pi pi-times-circle',
            self::CANCELLED => 'pi pi-ban',
        };
    }

    /**
     * Check if this status is final (no more transitions possible).
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::CANCELLED], true);
    }

    /**
     * Check if payout can be cancelled from this status.
     */
    public function canBeCancelled(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if payout can be retried from this status.
     */
    public function canBeRetried(): bool
    {
        return $this === self::FAILED;
    }

    /**
     * Get all statuses as options for forms.
     *
     * @return array<array{value: string, label: string, color: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ], self::cases());
    }
}
