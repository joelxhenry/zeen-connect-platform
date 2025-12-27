<?php

namespace App\Domains\Payment\Services;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Contracts\DirectSplitGatewayInterface;
use App\Domains\Payment\Contracts\EscrowGatewayInterface;
use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\DTOs\RefundResult;
use App\Domains\Payment\DTOs\SplitPaymentData;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Models\Payment;
use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Services\SubscriptionService;

class PaymentManager
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected GatewayResolver $gatewayResolver,
        protected LedgerService $ledgerService,
    ) {}

    /**
     * Get the appropriate gateway for a provider.
     */
    public function resolveGateway(Provider $provider): PaymentGatewayInterface
    {
        return $this->gatewayResolver->resolve($provider);
    }

    /**
     * Resolve gateway by name (for webhooks).
     */
    public function resolveGatewayByName(string $gatewayName): PaymentGatewayInterface
    {
        return $this->gatewayResolver->resolveByName($gatewayName);
    }

    /**
     * Get the commission rate for a provider.
     */
    public function getCommissionRate(Provider $provider): float
    {
        return $this->subscriptionService->getPlatformFeeRate($provider);
    }

    /**
     * Determine gateway type based on provider configuration.
     */
    public function determineGatewayType(Provider $provider): GatewayType
    {
        return $this->gatewayResolver->determineGatewayType($provider);
    }

    /**
     * Initialize payment for a booking.
     */
    public function initializePayment(
        Booking $booking,
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult {
        $provider = $booking->provider;
        $gateway = $this->resolveGateway($provider);

        // Update payment with gateway info
        $payment->update([
            'gateway' => $gateway->getProvider(),
            'gateway_type' => $gateway->getType(),
            'gateway_provider' => $gateway->getProvider(),
        ]);

        // Configure split if using split gateway
        if ($gateway instanceof DirectSplitGatewayInterface) {
            $providerConfig = $this->gatewayResolver->getProviderConfig($provider);

            if ($providerConfig) {
                $splitData = SplitPaymentData::fromPayment(
                    $payment,
                    $gateway->getPlatformMerchantId(),
                    $providerConfig->merchant_account_id
                );

                $gateway->configureSplit($payment, $splitData);
            }
        }

        return $gateway->initializePayment($payment, $returnUrl, $cancelUrl);
    }

    /**
     * Complete payment and handle post-payment logic.
     */
    public function completePayment(Payment $payment, array $callbackData): PaymentResult
    {
        $provider = $payment->booking->provider;
        $gateway = $this->resolveGateway($provider);

        $result = $gateway->completePayment($payment, $callbackData);

        // Handle escrow ledger entry on successful payment
        if ($result->success && $gateway instanceof EscrowGatewayInterface) {
            $gateway->recordToLedger($payment);
        }

        // Store split details if available
        if ($result->success && $result->splitDetails) {
            $payment->update([
                'split_details' => $result->splitDetails,
                'split_transaction_id' => $result->splitDetails['transaction_id'] ?? null,
            ]);
        }

        return $result;
    }

    /**
     * Process a refund.
     */
    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        $provider = $payment->booking->provider;
        $gateway = $this->resolveGateway($provider);

        return $gateway->refund($payment, $amount);
    }

    /**
     * Calculate fees for a booking/service.
     */
    public function calculateFees(Provider $provider, float $servicePrice): array
    {
        return $this->subscriptionService->calculateFees($provider, $servicePrice);
    }

    /**
     * Get provider's current balance summary.
     */
    public function getProviderBalanceSummary(Provider $provider): array
    {
        return $this->ledgerService->getBalanceSummary($provider->id);
    }

    /**
     * Check if provider has a linked payment account.
     */
    public function providerHasLinkedAccount(Provider $provider): bool
    {
        return $provider->gatewayConfigs()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->exists();
    }

    /**
     * Get the gateway type being used by a provider.
     */
    public function getProviderGatewayType(Provider $provider): string
    {
        $gatewayType = $this->determineGatewayType($provider);

        return $gatewayType->label();
    }
}
