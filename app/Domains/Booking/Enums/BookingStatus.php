<?php

namespace App\Domains\Booking\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::NO_SHOW => 'No Show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
            self::NO_SHOW => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PENDING => 'pi-clock',
            self::CONFIRMED => 'pi-check-circle',
            self::COMPLETED => 'pi-verified',
            self::CANCELLED => 'pi-times-circle',
            self::NO_SHOW => 'pi-exclamation-circle',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'color' => $case->color(),
        ], self::cases());
    }

    public function canTransitionTo(BookingStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::COMPLETED, self::CANCELLED, self::NO_SHOW]),
            self::COMPLETED, self::CANCELLED, self::NO_SHOW => false,
        };
    }
}
