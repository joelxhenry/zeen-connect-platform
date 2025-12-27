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
            self::FREE => [
                SubscriptionFeature::DIGITAL_STOREFRONT,
                SubscriptionFeature::EMAIL_NOTIFICATIONS,
                SubscriptionFeature::CLIENT_DATABASE,
                SubscriptionFeature::BOOKING_LINK,
            ],
            self::PREMIUM => [
                ...self::FREE->features(),
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
