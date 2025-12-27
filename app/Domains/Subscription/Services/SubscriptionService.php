<?php

namespace App\Domains\Subscription\Services;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\SubscriptionTier;

class SubscriptionService
{
    /**
     * Get the effective deposit percentage for a provider based on their tier.
     */
    public function getEffectiveDepositPercentage(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            SubscriptionTier::STARTER => (float) SystemSetting::get('free_tier_deposit_percentage', 20),
            SubscriptionTier::PREMIUM => $this->getPremiumDepositPercentage($provider),
            SubscriptionTier::ENTERPRISE => 0.0,
        };
    }

    /**
     * Get the premium tier deposit percentage, respecting minimum.
     */
    protected function getPremiumDepositPercentage(Provider $provider): float
    {
        $minimum = (float) SystemSetting::get('minimum_deposit_percentage', 15);
        $providerSetting = $provider->deposit_percentage;

        if ($providerSetting === null) {
            return $minimum;
        }

        return max((float) $providerSetting, $minimum);
    }

    /**
     * Get the platform fee rate for a provider based on their tier.
     */
    public function getPlatformFeeRate(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            SubscriptionTier::STARTER => (float) SystemSetting::get('free_tier_platform_fee_rate', 10) / 100,
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('premium_tier_platform_fee_rate', 2) / 100,
            SubscriptionTier::ENTERPRISE => 0.0,
        };
    }

    /**
     * Calculate all fees for a booking based on provider tier and service price.
     */
    public function calculateFees(Provider $provider, float $servicePrice): array
    {
        $tier = $provider->getTier();
        $depositPercentage = $this->getEffectiveDepositPercentage($provider);
        $platformFeeRate = $this->getPlatformFeeRate($provider);

        $depositAmount = round($servicePrice * $depositPercentage / 100, 2);
        $platformFee = round($servicePrice * $platformFeeRate, 2);

        // For enterprise, calculate processing fee from settings
        $processingFee = 0.0;
        if ($tier === SubscriptionTier::ENTERPRISE) {
            $processingFeeRate = (float) SystemSetting::get('enterprise_processing_fee_rate', 2.9) / 100;
            $processingFeeFlat = (float) SystemSetting::get('enterprise_processing_fee_flat', 50);
            $processingFee = round($servicePrice * $processingFeeRate + $processingFeeFlat, 2);
        }

        $providerPayout = $servicePrice - $platformFee;

        // If enterprise and provider pays processing fee, deduct it from payout
        if ($tier === SubscriptionTier::ENTERPRISE && $provider->processing_fee_payer === 'provider') {
            $providerPayout -= $processingFee;
        }

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),
            'service_price' => $servicePrice,
            'deposit_amount' => $depositAmount,
            'deposit_percentage' => $depositPercentage,
            'platform_fee' => $platformFee,
            'platform_fee_rate' => $platformFeeRate * 100,
            'processing_fee' => $processingFee,
            'processing_fee_payer' => $tier === SubscriptionTier::ENTERPRISE
                ? $provider->processing_fee_payer
                : null,
            'provider_payout' => $providerPayout,
            'requires_deposit' => $depositAmount > 0,
            'has_platform_fee' => $platformFee > 0,
        ];
    }

    /**
     * Calculate the payment amount based on payment type.
     */
    public function calculatePaymentAmount(
        Provider $provider,
        float $servicePrice,
        string $paymentType = 'full'
    ): array {
        $fees = $this->calculateFees($provider, $servicePrice);

        $amount = match ($paymentType) {
            'deposit' => $fees['deposit_amount'],
            'balance' => $servicePrice - $fees['deposit_amount'],
            default => $servicePrice,
        };

        // For enterprise tier with client-paid processing fee, add to payment amount
        $clientProcessingFee = 0.0;
        if ($fees['processing_fee_payer'] === 'client') {
            $clientProcessingFee = $fees['processing_fee'];
        }

        return [
            'amount' => $amount,
            'client_processing_fee' => $clientProcessingFee,
            'total_to_charge' => $amount + $clientProcessingFee,
            'payment_type' => $paymentType,
            ...$fees,
        ];
    }

    /**
     * Check if a provider's subscription is active.
     */
    public function isSubscriptionActive(Provider $provider): bool
    {
        $subscription = $provider->subscription;

        if (! $subscription) {
            // No subscription means free tier (always active)
            return true;
        }

        return $subscription->isActive();
    }

    /**
     * Create a free tier subscription for a new provider.
     */
    public function createFreeSubscription(Provider $provider): void
    {
        $provider->subscription()->create([
            'tier' => SubscriptionTier::STARTER,
            'started_at' => now(),
        ]);
    }

    /**
     * Calculate no-show deposit distribution.
     */
    public function calculateNoShowSplit(float $depositAmount): array
    {
        $providerPercentage = (float) SystemSetting::get('no_show_deposit_provider_percentage', 50);
        $platformPercentage = (float) SystemSetting::get('no_show_deposit_platform_percentage', 50);

        return [
            'provider_amount' => round($depositAmount * $providerPercentage / 100, 2),
            'platform_amount' => round($depositAmount * $platformPercentage / 100, 2),
            'provider_percentage' => $providerPercentage,
            'platform_percentage' => $platformPercentage,
        ];
    }

    /**
     * Get minimum deposit amount for Free tier.
     */
    public function getMinimumDepositAmount(): float
    {
        return (float) SystemSetting::get('minimum_deposit_amount', 500);
    }

    /**
     * Get tier monthly subscription price.
     */
    public function getTierMonthlyPrice(SubscriptionTier $tier): float
    {
        return match ($tier) {
            SubscriptionTier::STARTER => 0.0,
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('premium_tier_monthly_price', 3500),
            SubscriptionTier::ENTERPRISE => (float) SystemSetting::get('enterprise_tier_monthly_price', 20000),
        };
    }

    // =========================================================================
    // Team Member Fee Methods
    // =========================================================================

    /**
     * Get the monthly fee for each additional team member beyond free slots.
     */
    public function getExtraTeamMemberMonthlyFee(): float
    {
        return (float) SystemSetting::get('extra_team_member_monthly_fee', 1000);
    }

    /**
     * Get the number of free team member slots for premium tier (from settings).
     */
    public function getPremiumFreeTeamMemberSlots(): int
    {
        return (int) SystemSetting::get('premium_tier_free_team_members', 3);
    }

    /**
     * Calculate team member charges for a provider.
     *
     * @return array{
     *     tier: string,
     *     supports_team: bool,
     *     free_slots: int,
     *     active_count: int,
     *     extra_count: int,
     *     fee_per_extra: float,
     *     total_extra_fee: float,
     *     would_exceed_free_slots: bool
     * }
     */
    public function calculateTeamMemberCharges(Provider $provider): array
    {
        $tier = $provider->getTier();
        $supportsTeam = $tier->supportsTeam();
        $freeSlots = $supportsTeam ? $provider->getFreeTeamMemberSlots() : 0;
        $activeCount = $provider->getTeamMemberCount();
        $extraCount = $provider->getExtraTeamMemberCount();
        $feePerExtra = $this->getExtraTeamMemberMonthlyFee();

        // Enterprise tier has no extra fees
        $totalExtraFee = $tier === SubscriptionTier::ENTERPRISE
            ? 0.0
            : $extraCount * $feePerExtra;

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),
            'supports_team' => $supportsTeam,
            'free_slots' => $freeSlots === PHP_INT_MAX ? -1 : $freeSlots, // -1 indicates unlimited
            'active_count' => $activeCount,
            'extra_count' => $extraCount,
            'fee_per_extra' => $feePerExtra,
            'total_extra_fee' => $totalExtraFee,
            'would_exceed_free_slots' => $provider->wouldExceedFreeSlots(),
        ];
    }

    /**
     * Get a formatted description of team member limits for a tier.
     */
    public function getTeamMemberLimitDescription(SubscriptionTier $tier): string
    {
        return match ($tier) {
            SubscriptionTier::STARTER => 'Team members not available on Free tier',
            SubscriptionTier::PREMIUM => sprintf(
                '%d free members, then ₦%s/month each',
                $this->getPremiumFreeTeamMemberSlots(),
                number_format($this->getExtraTeamMemberMonthlyFee())
            ),
            SubscriptionTier::ENTERPRISE => 'Unlimited team members',
        };
    }

    // =========================================================================
    // Service Restriction Methods
    // =========================================================================

    /**
     * Get the minimum service price for a provider based on their tier.
     */
    public function getMinimumServicePrice(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            SubscriptionTier::STARTER => (float) SystemSetting::get('starter_tier_minimum_service_price', 1000),
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('premium_tier_minimum_service_price', 500),
            SubscriptionTier::ENTERPRISE => (float) SystemSetting::get('enterprise_tier_minimum_service_price', 0),
        };
    }

    /**
     * Get the minimum deposit percentage for a provider based on their tier.
     * This should be at least enough to cover the platform fee.
     */
    public function getMinimumDepositPercentage(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            // Starter tier: deposit must cover the platform fee
            SubscriptionTier::STARTER => (float) SystemSetting::get('free_tier_deposit_percentage', 20),
            // Premium tier: minimum deposit percentage from settings
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('minimum_deposit_percentage', 15),
            // Enterprise tier: no minimum required
            SubscriptionTier::ENTERPRISE => 0.0,
        };
    }

    /**
     * Get all tier restrictions for frontend display.
     */
    public function getTierRestrictions(Provider $provider): array
    {
        $tier = $provider->getTier();
        $platformFeeRate = $this->getPlatformFeeRate($provider) * 100;
        $minDepositPercentage = $this->getMinimumDepositPercentage($provider);
        $minServicePrice = $this->getMinimumServicePrice($provider);

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),
            'platform_fee_rate' => $platformFeeRate,
            'minimum_service_price' => $minServicePrice,
            'minimum_service_price_display' => $this->formatCurrency($minServicePrice),
            'minimum_deposit_percentage' => $minDepositPercentage,
            'can_disable_deposit' => $tier === SubscriptionTier::ENTERPRISE,
            'can_customize_deposit' => $tier !== SubscriptionTier::STARTER,
            'deposit_help_text' => $this->getDepositHelpText($tier, $minDepositPercentage, $platformFeeRate),
            'price_help_text' => $this->getPriceHelpText($tier, $minServicePrice),
        ];
    }

    /**
     * Get help text explaining deposit restrictions for a tier.
     */
    private function getDepositHelpText(SubscriptionTier $tier, float $minDeposit, float $platformFee): string
    {
        return match ($tier) {
            SubscriptionTier::STARTER => sprintf(
                'Starter tier requires a %d%% deposit to cover the %d%% platform fee.',
                (int) $minDeposit,
                (int) $platformFee
            ),
            SubscriptionTier::PREMIUM => sprintf(
                'Premium tier requires a minimum %d%% deposit. You can set a higher percentage.',
                (int) $minDeposit
            ),
            SubscriptionTier::ENTERPRISE => 'Enterprise tier has no deposit restrictions. You can set any deposit amount or none at all.',
        };
    }

    /**
     * Get help text explaining price restrictions for a tier.
     */
    private function getPriceHelpText(SubscriptionTier $tier, float $minPrice): string
    {
        if ($minPrice <= 0) {
            return 'No minimum service price for your tier.';
        }

        return sprintf(
            'Your %s tier requires a minimum service price of %s.',
            $tier->label(),
            $this->formatCurrency($minPrice)
        );
    }

    /**
     * Format a currency amount for display.
     */
    private function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
