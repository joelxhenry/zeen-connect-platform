<?php

namespace App\Domains\Payment\DTOs;

readonly class PaymentFeeResult
{
    public function __construct(
        // Base payment amount
        public float $amount,

        // Fee amounts for this payment
        public float $zeenFee,
        public float $gatewayFee,
        public float $totalFees,
        public float $convenienceFee,

        // What to charge the client
        public float $totalToCharge,

        // What goes to gateway (excludes their fee)
        public float $amountToGateway,

        // Base for processing fee calculation
        public float $processingBase,

        // Payment type
        public string $paymentType,

        // Provider receives after fees
        public float $providerReceives,

        // The underlying fee calculation
        public FeeResult $baseFees,
    ) {}

    /**
     * Convert to array (for backward compatibility).
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'zeen_fee' => $this->zeenFee,
            'gateway_fee' => $this->gatewayFee,
            'total_fees' => $this->totalFees,
            'convenience_fee' => $this->convenienceFee,
            'total_to_charge' => $this->totalToCharge,
            'amount_to_gateway' => $this->amountToGateway,
            'processing_base' => $this->processingBase,
            'payment_type' => $this->paymentType,
            'provider_receives' => $this->providerReceives,
            // Merge base fees for compatibility with existing code
            ...$this->baseFees->toArray(),
        ];
    }
}
