<?php

namespace App\Domains\Payment\DTOs;

readonly class SplitPaymentData
{
    public function __construct(
        public float $totalAmount,
        public float $platformAmount,
        public float $providerAmount,
        public string $platformMerchantId,
        public string $providerMerchantId,
        public string $currency = 'JMD',
        public ?float $processingFee = null,
        public ?string $processingFeePayer = null,
    ) {}

    /**
     * Create from a payment model.
     */
    public static function fromPayment(
        \App\Domains\Payment\Models\Payment $payment,
        string $platformMerchantId,
        string $providerMerchantId,
    ): self {
        return new self(
            totalAmount: $payment->amount,
            platformAmount: $payment->platform_fee,
            providerAmount: $payment->provider_amount,
            platformMerchantId: $platformMerchantId,
            providerMerchantId: $providerMerchantId,
            currency: $payment->currency,
            processingFee: $payment->processing_fee,
            processingFeePayer: $payment->processing_fee_payer,
        );
    }

    /**
     * Get the split configuration array for gateway APIs.
     */
    public function toSplitConfig(): array
    {
        return [
            [
                'account_id' => $this->platformMerchantId,
                'amount' => $this->platformAmount,
                'description' => 'Platform fee',
            ],
            [
                'account_id' => $this->providerMerchantId,
                'amount' => $this->providerAmount,
                'description' => 'Provider payment',
            ],
        ];
    }

    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'platform_amount' => $this->platformAmount,
            'provider_amount' => $this->providerAmount,
            'platform_merchant_id' => $this->platformMerchantId,
            'provider_merchant_id' => $this->providerMerchantId,
            'currency' => $this->currency,
            'processing_fee' => $this->processingFee,
            'processing_fee_payer' => $this->processingFeePayer,
        ];
    }
}
