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
            SubscriptionTier::FREE => (float) SystemSetting::get('free_tier_deposit_percentage', 20),
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
            SubscriptionTier::FREE => (float) SystemSetting::get('free_tier_platform_fee_rate', 10) / 100,
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
            'tier' => SubscriptionTier::FREE,
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
            SubscriptionTier::FREE => 0.0,
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('premium_tier_monthly_price', 3500),
            SubscriptionTier::ENTERPRISE => (float) SystemSetting::get('enterprise_tier_monthly_price', 20000),
        };
    }
}
