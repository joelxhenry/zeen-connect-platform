<?php

namespace App\Domains\Payment\Contracts;

use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\DTOs\RefundResult;
use App\Domains\Payment\DTOs\WebhookResult;
use App\Domains\Payment\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Get the gateway provider identifier (e.g., 'wipay', 'fygaro', 'powertranz').
     */
    public function getProvider(): string;

    /**
     * Get the gateway type ('split' or 'escrow').
     */
    public function getType(): string;

    /**
     * Initialize a payment session.
     * Returns a PaymentResult with redirect URL for hosted payment page.
     */
    public function initializePayment(
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult;

    /**
     * Complete/verify a payment after customer returns from hosted payment page.
     */
    public function completePayment(Payment $payment, array $callbackData): PaymentResult;

    /**
     * Process a refund for a completed payment.
     *
     * @param  Payment  $payment  The payment to refund
     * @param  float|null  $amount  Amount to refund (null for full refund)
     */
    public function refund(Payment $payment, ?float $amount = null): RefundResult;

    /**
     * Handle and process a webhook notification from the gateway.
     */
    public function handleWebhook(Request $request): WebhookResult;

    /**
     * Verify the authenticity of a webhook request (signature validation).
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Check if the gateway is properly configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Get the list of currencies supported by this gateway.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array;
}
