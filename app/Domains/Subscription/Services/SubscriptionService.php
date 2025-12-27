<?php

namespace App\Domains\Subscription\Services;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\SubscriptionTier;

class SubscriptionService
{
    // =========================================================================
    // Fee Rate Methods
    // =========================================================================

    /**
     * Get the gateway fee rate (percentage).
     * This is a single admin-configured rate for all tiers.
     */
    public function getGatewayFeeRate(): float
    {
        return (float) SystemSetting::get('gateway_fee_rate', 4.0);
    }

    /**
     * Get the Zeen platform fee rate for a provider based on their tier (percentage).
     */
    public function getZeenFeeRate(Provider $provider): float
    {
        return $provider->getTier()->zeenFeeRate();
    }

    /**
     * Get the total fee rate for a provider (Zeen + Gateway).
     */
    public function getTotalFeeRate(Provider $provider): float
    {
        return $this->getZeenFeeRate($provider) + $this->getGatewayFeeRate();
    }

    /**
     * Get the platform fee rate for a provider based on their tier.
     * @deprecated Use getZeenFeeRate() instead. Returns decimal for backwards compatibility.
     */
    public function getPlatformFeeRate(Provider $provider): float
    {
        return $this->getZeenFeeRate($provider) / 100;
    }

    // =========================================================================
    // Fee Calculation Methods
    // =========================================================================

    /**
     * Calculate all fees for a booking based on provider tier and service price.
     * Returns separated Zeen fee and Gateway fee.
     *
     * Fee calculation logic:
     * - Zeen fee: percentage of full service price (platform fee)
     * - Gateway fee: percentage of the amount being charged (deposit or full)
     */
    public function calculateFees(Provider $provider, float $servicePrice): array
    {
        $tier = $provider->getTier();
        $feePayer = $provider->fee_payer ?? 'provider';

        // Get fee rates as percentages
        $zeenFeeRate = $this->getZeenFeeRate($provider);
        $gatewayFeeRate = $this->getGatewayFeeRate();

        // Calculate deposit first (needed for gateway fee calculation)
        $depositPercentage = $this->getEffectiveDepositPercentage($provider);
        $depositAmount = round($servicePrice * $depositPercentage / 100, 2);
        $requiresDeposit = $depositAmount > 0;

        // Zeen fee is always based on full service price
        $zeenFee = round($servicePrice * ($zeenFeeRate / 100), 2);

        // Gateway fee is based on what's actually being charged
        // If deposit required: gateway fee on deposit amount
        // If full payment: gateway fee on full service price
        $chargeAmount = $requiresDeposit ? $depositAmount : $servicePrice;
        $gatewayFee = round($chargeAmount * ($gatewayFeeRate / 100), 2);

        $totalFees = $zeenFee + $gatewayFee;

        // Calculate amounts based on who pays the fees
        if ($feePayer === 'client') {
            // Client pays: add fees on top as "convenience fee"
            $convenienceFee = $totalFees;
            $clientPays = $servicePrice + $convenienceFee;
            $providerReceives = $servicePrice;
        } else {
            // Provider pays: deduct fees from their payout
            $convenienceFee = 0.0;
            $clientPays = $servicePrice;
            $providerReceives = $servicePrice - $totalFees;
        }

        // Combined rate for display purposes
        $totalFeeRate = $zeenFeeRate + $gatewayFeeRate;

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),
            'service_price' => $servicePrice,

            'deposit_amount' => $depositAmount,
            'deposit_percentage' => $depositPercentage,
            'requires_deposit' => $requiresDeposit,

            // New separated fee structure
            'zeen_fee' => $zeenFee,
            'zeen_fee_rate' => $zeenFeeRate,
            'gateway_fee' => $gatewayFee,
            'gateway_fee_rate' => $gatewayFeeRate,
            'total_fees' => $totalFees,
            'total_fee_rate' => $totalFeeRate,

            // Legacy aliases for backwards compatibility
            'platform_fee' => $totalFees,
            'platform_fee_rate' => $totalFeeRate,

            'convenience_fee' => $convenienceFee,
            'fee_payer' => $feePayer,
            'client_pays' => $clientPays,
            'provider_receives' => $providerReceives,
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

        $baseAmount = match ($paymentType) {
            'deposit' => $fees['deposit_amount'],
            'balance' => $servicePrice - $fees['deposit_amount'],
            default => $servicePrice,
        };

        // For client-pays fees, calculate the fee portion for this payment
        $convenienceFee = 0.0;
        if ($fees['fee_payer'] === 'client' && $paymentType === 'full') {
            $convenienceFee = $fees['convenience_fee'];
        } elseif ($fees['fee_payer'] === 'client' && $paymentType === 'deposit') {
            // Proportional convenience fee for deposit
            $depositRatio = $fees['deposit_amount'] / $servicePrice;
            $convenienceFee = round($fees['convenience_fee'] * $depositRatio, 2);
        }

        return [
            'amount' => $baseAmount,
            'convenience_fee' => $convenienceFee,
            'total_to_charge' => $baseAmount + $convenienceFee,
            'payment_type' => $paymentType,
            ...$fees,
        ];
    }

    // =========================================================================
    // Deposit Methods
    // =========================================================================

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
        return $tier->monthlyPrice();
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
        $zeenFeeRate = $this->getZeenFeeRate($provider);
        $gatewayFeeRate = $this->getGatewayFeeRate();
        $totalFeeRate = $zeenFeeRate + $gatewayFeeRate;
        $minDepositPercentage = $this->getMinimumDepositPercentage($provider);
        $minServicePrice = $this->getMinimumServicePrice($provider);
        $teamSlots = $tier->teamSlots();

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),

            // Fee rates
            'zeen_fee_rate' => $zeenFeeRate,
            'gateway_fee_rate' => $gatewayFeeRate,
            'total_fee_rate' => $totalFeeRate,
            'platform_fee_rate' => $totalFeeRate, // Legacy alias

            // Team slots
            'team_slots' => $teamSlots === PHP_INT_MAX ? 'unlimited' : $teamSlots,

            // Monthly price
            'monthly_price' => $tier->monthlyPrice(),

            // Service restrictions
            'minimum_service_price' => $minServicePrice,
            'minimum_service_price_display' => $this->formatCurrency($minServicePrice),
            'minimum_deposit_percentage' => $minDepositPercentage,

            // Capabilities
            'can_disable_deposit' => $tier === SubscriptionTier::ENTERPRISE,
            'can_customize_deposit' => $tier !== SubscriptionTier::STARTER,
            'can_pass_fees_to_client' => true, // All tiers can now do this

            // Help text
            'deposit_help_text' => $this->getDepositHelpText($tier, $minDepositPercentage, $totalFeeRate),
            'price_help_text' => $this->getPriceHelpText($tier, $minServicePrice),
            'fee_help_text' => $this->getFeeHelpText($tier, $zeenFeeRate, $gatewayFeeRate),
        ];
    }

    /**
     * Get help text explaining fee structure for a tier.
     */
    private function getFeeHelpText(SubscriptionTier $tier, float $zeenFee, float $gatewayFee): string
    {
        $totalFee = $zeenFee + $gatewayFee;

        return sprintf(
            '%s tier: %.1f%% Zeen fee + %.1f%% gateway fee = %.1f%% total per transaction.',
            $tier->label(),
            $zeenFee,
            $gatewayFee,
            $totalFee
        );
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
