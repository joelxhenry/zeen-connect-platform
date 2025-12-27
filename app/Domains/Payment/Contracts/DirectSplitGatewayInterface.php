<?php

namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\DTOs\SplitPaymentData;
use App\Domains\Payment\Models\Payment;

interface DirectSplitGatewayInterface extends PaymentGatewayInterface
{
    /**
     * Configure the split payment recipients.
     * This should be called before initializePayment for split payments.
     */
    public function configureSplit(Payment $payment, SplitPaymentData $splitData): void;

    /**
     * Get split transaction details after payment completion.
     *
     * @return array{platform: array, provider: array}
     */
    public function getSplitDetails(string $transactionId): array;

    /**
     * Get the platform's merchant account ID for this gateway.
     */
    public function getPlatformMerchantId(): string;

    /**
     * Check if the provider credentials are valid.
     */
    public function validateProviderCredentials(array $credentials): bool;
}
