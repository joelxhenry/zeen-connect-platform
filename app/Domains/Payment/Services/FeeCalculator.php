<?php

namespace App\Domains\Payment\Services;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\DTOs\FeeResult;
use App\Domains\Payment\DTOs\PaymentFeeResult;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;

class FeeCalculator
{
    // =========================================================================
    // Main Public API
    // =========================================================================

    /**
     * Calculate fees with priority chain: booking -> provider -> system -> defaults.
     * If booking has stored fees, returns them immediately (no recalculation).
     */
    public function calculateFees(
        Provider $provider,
        float $servicePrice,
        ?Service $service = null,
        ?Booking $booking = null
    ): FeeResult {
        // Priority 1: Return stored booking fees if they exist
        if ($booking !== null && $this->hasStoredFees($booking)) {
            return FeeResult::fromBooking($booking);
        }

        // Priority 2-4: Calculate from provider -> system -> defaults
        return $this->calculate($provider, $servicePrice, $service);
    }

    /**
     * Calculate payment-specific fees (deposit, balance, or full).
     */
    public function calculatePaymentAmount(
        Provider $provider,
        float $servicePrice,
        string $paymentType = 'full',
        ?Service $service = null,
        ?Booking $booking = null
    ): PaymentFeeResult {
        $baseFees = $this->calculateFees($provider, $servicePrice, $service, $booking);

        return $this->calculatePaymentFromFees($baseFees, $paymentType, $servicePrice);
    }

    /**
     * Get fees for an existing booking (uses stored fees if available).
     */
    public function getBookingFees(Booking $booking): FeeResult
    {
        return $this->calculateFees(
            $booking->provider,
            (float) $booking->service_price,
            $booking->service,
            $booking
        );
    }

    // =========================================================================
    // Fee Rate Methods
    // =========================================================================

    /**
     * Get Zeen platform fee rate for a provider (percentage).
     * Founding members with active waiver get 0%.
     */
    public function getZeenFeeRate(Provider $provider): float
    {
        if ($provider->hasFoundingFeeWaiver()) {
            return 0.0;
        }

        return $provider->getTier()->zeenFeeRate();
    }

    /**
     * Get gateway fee rate from system settings (percentage).
     */
    public function getGatewayFeeRate(): float
    {
        return (float) SystemSetting::get('gateway_fee_rate', 4.0);
    }

    /**
     * Get total combined fee rate.
     */
    public function getTotalFeeRate(Provider $provider): float
    {
        return $this->getZeenFeeRate($provider) + $this->getGatewayFeeRate();
    }

    // =========================================================================
    // Deposit Calculation Methods
    // =========================================================================

    /**
     * Calculate deposit amount based on service/provider settings and tier.
     *
     * @return array{amount: float, percentage: float}
     */
    public function calculateDepositAmount(
        Provider $provider,
        float $servicePrice,
        ?Service $service = null
    ): array {
        // If no service provided, use tier-based percentage
        if ($service === null) {
            $percentage = $this->getEffectiveDepositPercentage($provider);

            return [
                'amount' => round($servicePrice * $percentage / 100, 2),
                'percentage' => $percentage,
            ];
        }

        // Get tier restrictions to know if deposit can be disabled
        $tier = $provider->getTier();
        $canDisableDeposit = $tier === SubscriptionTier::ENTERPRISE;
        $minPercentage = $this->getMinimumDepositPercentage($provider);

        // Get the service's effective booking settings (uses provider defaults if enabled)
        $settings = $service->getEffectiveBookingSettings();
        $depositType = $settings['deposit_type'] ?? 'none';
        $depositAmount = $settings['deposit_amount'] ?? null;

        // If deposit is set to 'none' but tier doesn't allow disabling deposits,
        // fall back to tier-based minimum deposit percentage
        if ($depositType === 'none') {
            if ($canDisableDeposit) {
                return [
                    'amount' => 0.0,
                    'percentage' => 0.0,
                ];
            }

            // Tier requires deposit - use tier-based percentage
            $percentage = $this->getEffectiveDepositPercentage($provider);

            return [
                'amount' => round($servicePrice * $percentage / 100, 2),
                'percentage' => $percentage,
            ];
        }

        // Percentage-based deposit (only type supported now)
        if ($depositType === 'percentage' && $depositAmount !== null) {
            // Ensure minimum deposit percentage is respected
            $effectivePercentage = max((float) $depositAmount, $minPercentage);

            return [
                'amount' => round($servicePrice * $effectivePercentage / 100, 2),
                'percentage' => $effectivePercentage,
            ];
        }

        // Fallback to tier-based percentage
        $percentage = $this->getEffectiveDepositPercentage($provider);

        return [
            'amount' => round($servicePrice * $percentage / 100, 2),
            'percentage' => $percentage,
        ];
    }

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
     * Get the minimum deposit percentage for a provider based on their tier.
     */
    public function getMinimumDepositPercentage(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            SubscriptionTier::STARTER => (float) SystemSetting::get('free_tier_deposit_percentage', 20),
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('minimum_deposit_percentage', 15),
            SubscriptionTier::ENTERPRISE => 0.0,
        };
    }

    // =========================================================================
    // Private Implementation Methods
    // =========================================================================

    /**
     * Check if booking has stored fee values.
     */
    private function hasStoredFees(Booking $booking): bool
    {
        return $booking->zeen_fee !== null
            && $booking->gateway_fee !== null
            && $booking->fee_payer !== null;
    }

    /**
     * Core calculation logic.
     */
    private function calculate(
        Provider $provider,
        float $servicePrice,
        ?Service $service
    ): FeeResult {
        $tier = $provider->getTier();
        $feePayer = $provider->fee_payer ?? 'provider';

        // Get fee rates as percentages
        $zeenFeeRate = $this->getZeenFeeRate($provider);
        $gatewayFeeRate = $this->getGatewayFeeRate();

        // Calculate deposit based on service settings or provider tier
        $depositData = $this->calculateDepositAmount($provider, $servicePrice, $service);
        $depositAmount = $depositData['amount'];
        $depositPercentage = $depositData['percentage'];
        $requiresDeposit = $depositAmount > 0;

        // Zeen fee is always based on full service price
        $zeenFee = round($servicePrice * ($zeenFeeRate / 100), 2);

        // Base charge amount (deposit or full service price)
        $chargeAmount = $requiresDeposit ? $depositAmount : $servicePrice;

        // Gateway fee is calculated on (chargeAmount + zeenFee)
        // This is for display purposes - WiPay handles their own fee calculation
        $processingBase = $chargeAmount + $zeenFee;
        $gatewayFee = round($processingBase * ($gatewayFeeRate / 100), 2);

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

        // Amount to send to gateway API (excludes processing fee - WiPay handles their own fee)
        $amountToGateway = $feePayer === 'client'
            ? $chargeAmount + $zeenFee
            : $chargeAmount;

        return new FeeResult(
            servicePrice: $servicePrice,
            zeenFee: $zeenFee,
            gatewayFee: $gatewayFee,
            totalFees: $totalFees,
            convenienceFee: $convenienceFee,
            zeenFeeRate: $zeenFeeRate,
            gatewayFeeRate: $gatewayFeeRate,
            totalFeeRate: $totalFeeRate,
            feePayer: $feePayer,
            depositAmount: $depositAmount,
            depositPercentage: $depositPercentage,
            requiresDeposit: $requiresDeposit,
            clientPays: $clientPays,
            providerReceives: $providerReceives,
            amountToGateway: $amountToGateway,
            processingBase: $processingBase,
            tier: $tier->value,
            tierLabel: $tier->label(),
            source: 'calculated',
        );
    }

    /**
     * Calculate payment-specific fees from base fees.
     */
    private function calculatePaymentFromFees(
        FeeResult $fees,
        string $paymentType,
        float $servicePrice
    ): PaymentFeeResult {
        $feePayer = $fees->feePayer;
        $gatewayFeeRate = $fees->gatewayFeeRate;

        $baseAmount = match ($paymentType) {
            'deposit' => $fees->depositAmount,
            'balance' => $servicePrice - $fees->depositAmount,
            default => $servicePrice,
        };

        // Calculate fees based on payment type
        if ($paymentType === 'balance') {
            // Balance payment: no Zeen fee (already charged), only processing fee on balance
            $zeenFee = 0.0;
            $processingBase = $baseAmount;
            $processingFee = round($processingBase * ($gatewayFeeRate / 100), 2);
            $totalFees = $processingFee;
        } else {
            // Full or deposit payment: use calculated fees
            $zeenFee = $fees->zeenFee;
            $processingBase = $fees->processingBase;
            $processingFee = $fees->gatewayFee;
            $totalFees = $fees->totalFees;
        }

        // Determine convenience fee and amount to gateway based on who pays
        if ($feePayer === 'client') {
            $convenienceFee = $zeenFee + $processingFee;
            $totalToCharge = $baseAmount + $convenienceFee;
            // Gateway receives base amount + zeen fee (excludes processing fee)
            $amountToGateway = $baseAmount + $zeenFee;
        } else {
            $convenienceFee = 0.0;
            $totalToCharge = $baseAmount;
            // Gateway receives base amount only (zeen fee deducted from provider payout)
            $amountToGateway = $baseAmount;
        }

        // Calculate what provider receives for this payment
        $providerReceives = $baseAmount - ($feePayer === 'provider' ? $totalFees : 0);

        return new PaymentFeeResult(
            amount: $baseAmount,
            zeenFee: $zeenFee,
            gatewayFee: $processingFee,
            totalFees: $totalFees,
            convenienceFee: $convenienceFee,
            totalToCharge: $totalToCharge,
            amountToGateway: $amountToGateway,
            processingBase: $processingBase,
            paymentType: $paymentType,
            providerReceives: $providerReceives,
            baseFees: $fees,
        );
    }

    /**
     * Get the premium tier deposit percentage, respecting minimum.
     */
    private function getPremiumDepositPercentage(Provider $provider): float
    {
        $minimum = (float) SystemSetting::get('minimum_deposit_percentage', 15);
        $providerSetting = $provider->deposit_percentage;

        if ($providerSetting === null) {
            return $minimum;
        }

        return max((float) $providerSetting, $minimum);
    }
}
