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

    /**
     * Check if this tier supports team members.
     */
    public function supportsTeam(): bool
    {
        return $this !== self::FREE;
    }

    /**
     * Get the team member limit for this tier.
     * Returns null for unlimited, 0 for no team support.
     */
    public function teamMemberLimit(): ?int
    {
        return match ($this) {
            self::FREE => 0,
            self::PREMIUM => 3,       // Soft limit with extra charges
            self::ENTERPRISE => null, // Unlimited
        };
    }

    /**
     * Get the number of free team member slots.
     * For unlimited tiers, returns PHP_INT_MAX for comparison purposes.
     */
    public function freeTeamMemberSlots(): int
    {
        return match ($this) {
            self::FREE => 0,
            self::PREMIUM => 3,
            self::ENTERPRISE => PHP_INT_MAX,
        };
    }
}
