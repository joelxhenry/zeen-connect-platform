<?php

namespace App\Domains\Payment\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case PARTIALLY_REFUNDED = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
            self::PARTIALLY_REFUNDED => 'Partially Refunded',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warn',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'secondary',
            self::PARTIALLY_REFUNDED => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'pi-clock',
            self::PROCESSING => 'pi-spin pi-spinner',
            self::COMPLETED => 'pi-check-circle',
            self::FAILED => 'pi-times-circle',
            self::REFUNDED => 'pi-replay',
            self::PARTIALLY_REFUNDED => 'pi-replay',
        };
    }

    public function isSuccessful(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::REFUNDED]);
    }

    public static function options(): array
    {
        return collect(self::cases())->map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all();
    }
}
