<?php

namespace App\Domains\Payment\DTOs;

use App\Domains\Booking\Models\Booking;

readonly class FeeResult
{
    public function __construct(
        // Core pricing
        public float $servicePrice,

        // Fee amounts
        public float $zeenFee,
        public float $gatewayFee,
        public float $totalFees,
        public float $convenienceFee,

        // Fee rates (percentages)
        public float $zeenFeeRate,
        public float $gatewayFeeRate,
        public float $totalFeeRate,

        // Fee payer configuration
        public string $feePayer,

        // Deposit information
        public float $depositAmount,
        public float $depositPercentage,
        public bool $requiresDeposit,

        // Calculated payment amounts
        public float $clientPays,
        public float $providerReceives,
        public float $amountToGateway,
        public float $processingBase,

        // Tier information
        public string $tier,
        public string $tierLabel,

        // Source indicator (for debugging/tracing)
        public string $source = 'calculated',
    ) {}

    /**
     * Create from stored booking fees (no recalculation).
     */
    public static function fromBooking(Booking $booking): self
    {
        $servicePrice = (float) $booking->service_price;
        $zeenFee = (float) ($booking->zeen_fee ?? 0);
        $gatewayFee = (float) ($booking->gateway_fee ?? 0);
        $convenienceFee = (float) ($booking->convenience_fee ?? 0);
        $feePayer = $booking->fee_payer ?? 'provider';
        $depositAmount = (float) ($booking->deposit_amount ?? 0);

        $totalFees = $zeenFee + $gatewayFee;
        $requiresDeposit = $depositAmount > 0;

        // Calculate amounts based on stored values
        if ($feePayer === 'client') {
            $clientPays = $servicePrice + $convenienceFee;
            $providerReceives = $servicePrice;
        } else {
            $clientPays = $servicePrice;
            $providerReceives = $servicePrice - $totalFees;
        }

        // Derive rates from provider (for display purposes)
        $provider = $booking->provider;
        $tier = $provider->getTier();
        $zeenFeeRate = $servicePrice > 0 ? ($zeenFee / $servicePrice) * 100 : 0;
        $gatewayFeeRate = 0;

        // Calculate gateway fee rate from processing base
        $chargeAmount = $requiresDeposit ? $depositAmount : $servicePrice;
        $processingBase = $chargeAmount + $zeenFee;
        if ($processingBase > 0 && $gatewayFee > 0) {
            $gatewayFeeRate = ($gatewayFee / $processingBase) * 100;
        }

        $totalFeeRate = $zeenFeeRate + $gatewayFeeRate;

        // Amount to gateway
        $amountToGateway = $feePayer === 'client'
            ? $chargeAmount + $zeenFee
            : $chargeAmount;

        // Deposit percentage
        $depositPercentage = $servicePrice > 0
            ? ($depositAmount / $servicePrice) * 100
            : 0;

        return new self(
            servicePrice: $servicePrice,
            zeenFee: $zeenFee,
            gatewayFee: $gatewayFee,
            totalFees: $totalFees,
            convenienceFee: $convenienceFee,
            zeenFeeRate: round($zeenFeeRate, 2),
            gatewayFeeRate: round($gatewayFeeRate, 2),
            totalFeeRate: round($totalFeeRate, 2),
            feePayer: $feePayer,
            depositAmount: $depositAmount,
            depositPercentage: round($depositPercentage, 2),
            requiresDeposit: $requiresDeposit,
            clientPays: $clientPays,
            providerReceives: $providerReceives,
            amountToGateway: $amountToGateway,
            processingBase: $processingBase,
            tier: $tier->value,
            tierLabel: $tier->label(),
            source: 'booking',
        );
    }

    /**
     * Convert to array (for backward compatibility with existing callers).
     */
    public function toArray(): array
    {
        return [
            // Core pricing
            'service_price' => $this->servicePrice,

            // Fee amounts
            'zeen_fee' => $this->zeenFee,
            'gateway_fee' => $this->gatewayFee,
            'total_fees' => $this->totalFees,
            'convenience_fee' => $this->convenienceFee,

            // Fee rates
            'zeen_fee_rate' => $this->zeenFeeRate,
            'gateway_fee_rate' => $this->gatewayFeeRate,
            'total_fee_rate' => $this->totalFeeRate,

            // Fee payer
            'fee_payer' => $this->feePayer,

            // Deposit info
            'deposit_amount' => $this->depositAmount,
            'deposit_percentage' => $this->depositPercentage,
            'requires_deposit' => $this->requiresDeposit,

            // Calculated amounts
            'client_pays' => $this->clientPays,
            'provider_receives' => $this->providerReceives,
            'amount_to_gateway' => $this->amountToGateway,
            'processing_base' => $this->processingBase,

            // Tier info
            'tier' => $this->tier,
            'tier_label' => $this->tierLabel,

            // Legacy aliases for backwards compatibility
            'platform_fee' => $this->totalFees,
            'platform_fee_rate' => $this->totalFeeRate,
        ];
    }
}
