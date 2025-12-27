<?php

namespace App\Domains\Subscription\Enums;

use App\Domains\Admin\Models\SystemSetting;

enum SubscriptionTier: string
{
    case STARTER = 'starter';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    public function label(): string
    {
        return match ($this) {
            self::STARTER => 'Starter',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STARTER => 'secondary',
            self::PREMIUM => 'info',
            self::ENTERPRISE => 'success',
        };
    }

    /**
     * Get the Zeen platform fee rate for this tier (percentage).
     */
    public function zeenFeeRate(): float
    {
        return match ($this) {
            self::STARTER => (float) SystemSetting::get('starter_zeen_fee_rate', 3.0),
            self::PREMIUM => (float) SystemSetting::get('premium_zeen_fee_rate', 1.5),
            self::ENTERPRISE => (float) SystemSetting::get('enterprise_zeen_fee_rate', 0.5),
        };
    }

    /**
     * Get the monthly subscription price for this tier (JMD).
     */
    public function monthlyPrice(): float
    {
        return match ($this) {
            self::STARTER => (float) SystemSetting::get('starter_monthly_price', 0),
            self::PREMIUM => (float) SystemSetting::get('premium_monthly_price', 4000),
            self::ENTERPRISE => (float) SystemSetting::get('enterprise_monthly_price', 15000),
        };
    }

    /**
     * Get the team member slots for this tier.
     * Returns PHP_INT_MAX for unlimited.
     */
    public function teamSlots(): int
    {
        return match ($this) {
            self::STARTER => (int) SystemSetting::get('starter_team_slots', 1),
            self::PREMIUM => (int) SystemSetting::get('premium_team_slots', 5),
            self::ENTERPRISE => PHP_INT_MAX,
        };
    }

    /**
     * @deprecated Use zeenFeeRate() instead. This returns the old format (decimal).
     */
    public function depositPercentage(): float
    {
        return match ($this) {
            self::STARTER => 20.0,
            self::PREMIUM => 15.0,
            self::ENTERPRISE => 0.0,
        };
    }

    /**
     * @deprecated Use zeenFeeRate() instead. This returns the old format (decimal).
     */
    public function platformFeeRate(): float
    {
        // Return as decimal for backwards compatibility
        return $this->zeenFeeRate() / 100;
    }

    public function requiresDeposit(): bool
    {
        return $this !== self::ENTERPRISE;
    }

    public function hasPlatformFee(): bool
    {
        return $this->zeenFeeRate() > 0;
    }

    /**
     * Check if this tier supports team members.
     */
    public function supportsTeam(): bool
    {
        return $this->teamSlots() > 1;
    }

    /**
     * Get the team member limit for this tier.
     * Returns null for unlimited, otherwise the number of slots.
     * @deprecated Use teamSlots() instead.
     */
    public function teamMemberLimit(): ?int
    {
        $slots = $this->teamSlots();

        return $slots === PHP_INT_MAX ? null : $slots;
    }

    /**
     * Get the number of free team member slots.
     * For unlimited tiers, returns PHP_INT_MAX for comparison purposes.
     * @deprecated Use teamSlots() instead.
     */
    public function freeTeamMemberSlots(): int
    {
        return $this->teamSlots();
    }

    /**
     * Check if this tier has access to a specific feature.
     */
    public function hasFeature(SubscriptionFeature $feature): bool
    {
        return in_array($feature, $this->features(), true);
    }

    /**
     * Get all features available for this tier.
     *
     * @return array<SubscriptionFeature>
     */
    public function features(): array
    {
        return match ($this) {
            self::STARTER => [
                SubscriptionFeature::DIGITAL_STOREFRONT,
                SubscriptionFeature::EMAIL_NOTIFICATIONS,
                SubscriptionFeature::CLIENT_DATABASE,
                SubscriptionFeature::BOOKING_LINK,
            ],
            self::PREMIUM => [
                ...self::STARTER->features(),
                SubscriptionFeature::TEAM_MEMBERS,
                SubscriptionFeature::WHATSAPP_NOTIFICATIONS,
                SubscriptionFeature::PRIORITY_LISTING,
            ],
            self::ENTERPRISE => [
                ...self::PREMIUM->features(),
                SubscriptionFeature::EMBEDDABLE_WIDGETS,
                SubscriptionFeature::API_ACCESS,
                SubscriptionFeature::WHITE_LABELING,
                SubscriptionFeature::CUSTOM_DEPOSITS,
            ],
        };
    }

    /**
     * Get all features with their availability status for this tier.
     *
     * @return array<array{feature: SubscriptionFeature, available: bool, value: string, label: string, description: string, icon: string}>
     */
    public function allFeaturesWithStatus(): array
    {
        $availableFeatures = $this->features();

        return array_map(fn (SubscriptionFeature $feature) => [
            'feature' => $feature,
            'available' => in_array($feature, $availableFeatures, true),
            'value' => $feature->value,
            'label' => $feature->label(),
            'description' => $feature->description(),
            'icon' => $feature->icon(),
        ], SubscriptionFeature::all());
    }
}
