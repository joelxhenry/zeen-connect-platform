<?php

namespace App\Domains\Payment\Enums;

enum PayoutStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Review',
            self::APPROVED => 'Approved',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warn',
            self::APPROVED => 'info',
            self::PROCESSING => 'info',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'pi-clock',
            self::APPROVED => 'pi-check',
            self::PROCESSING => 'pi-spin pi-spinner',
            self::COMPLETED => 'pi-check-circle',
            self::FAILED => 'pi-times-circle',
            self::CANCELLED => 'pi-ban',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all();
    }
}
