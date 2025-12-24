<?php

namespace App\Domains\Subscription\Enums;

enum SubscriptionTier: string
{
    case FREE = 'free';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::FREE => 'Free',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FREE => 'secondary',
            self::PREMIUM => 'info',
            self::ENTERPRISE => 'success',
        };
    }

    public function depositPercentage(): float
    {
        return match ($this) {
            self::FREE => 20.0,
            self::PREMIUM => 15.0, // Minimum, provider can set higher
            self::ENTERPRISE => 0.0,
        };
    }

    public function platformFeeRate(): float
    {
        return match ($this) {
            self::FREE => 0.10,      // 10%
            self::PREMIUM => 0.02,   // 2%
            self::ENTERPRISE => 0.0, // 0%
        };
    }

    public function requiresDeposit(): bool
    {
        return $this !== self::ENTERPRISE;
    }

    public function hasPlatformFee(): bool
    {
        return $this !== self::ENTERPRISE;
    }
}
