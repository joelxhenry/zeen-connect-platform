<?php

namespace App\Domains\Subscription\Services;

use App\Domains\Payment\Services\PowerTranzGateway;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\ProviderPaymentMethod;
use App\Domains\Subscription\Enums\InvoiceStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\Subscription;
use App\Domains\Subscription\Models\SubscriptionInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SubscriptionPaymentService
{
    public function __construct(
        protected PowerTranzGateway $gateway
    ) {}

    /**
     * Initiate an upgrade to a paid tier.
     * Creates a pending invoice and redirects to payment gateway.
     *
     * @return array{success: bool, redirect_url?: string, invoice?: SubscriptionInvoice, error?: string}
     */
    public function initiateUpgrade(
        Provider $provider,
        SubscriptionTier $tier,
        string $billingCycle,
        string $returnUrl
    ): array {
        // Validate upgrade is allowed
        if (! $tier->isPaid()) {
            return [
                'success' => false,
                'error' => 'Cannot upgrade to a free tier. Use downgrade instead.',
            ];
        }

        $currentTier = $provider->getTier();
        if ($currentTier === $tier && ! $provider->subscription?->isOnTrial()) {
            return [
                'success' => false,
                'error' => 'You are already on this tier.',
            ];
        }

        // Calculate price
        $amount = $tier->price($billingCycle);
        $currency = 'JMD';

        // Generate order ID
        $orderId = 'SUB-' . strtoupper(Str::random(12));

        // Create pending invoice
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $provider->subscription->id,
            'provider_id' => $provider->id,
            'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
            'tier' => $tier->value,
            'billing_cycle' => $billingCycle,
            'amount' => $amount,
            'currency' => $currency,
            'status' => InvoiceStatus::PENDING,
            'period_start' => now(),
            'period_end' => $billingCycle === 'annual' ? now()->addYear() : now()->addMonth(),
        ]);

        // Get provider's user for email/name
        $user = $provider->user;

        // Initialize payment with PowerTranz
        $result = $this->gateway->initializeSubscriptionPayment(
            amount: $amount,
            currency: $currency,
            orderId: $orderId,
            returnUrl: $returnUrl . '?invoice=' . $invoice->uuid,
            email: $user->email,
            name: $user->name
        );

        if (! $result['success']) {
            // Mark invoice as failed
            $invoice->markAsFailed($result['error'] ?? 'Payment initialization failed');

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Payment initialization failed',
            ];
        }

        // Store order ID on invoice for later verification
        $invoice->update([
            'powertranz_transaction_id' => $orderId,
        ]);

        return [
            'success' => true,
            'redirect_url' => $result['redirect_url'],
            'invoice' => $invoice,
        ];
    }

    /**
     * Complete an upgrade after successful payment.
     * Tokenizes card, creates recurring profile, activates subscription.
     */
    public function completeUpgrade(string $spiToken, SubscriptionInvoice $invoice): array
    {
        // Verify payment with PowerTranz
        $result = $this->gateway->verifySubscriptionPayment($spiToken);

        if (! $result['success']) {
            $invoice->markAsFailed($result['error'] ?? 'Payment verification failed', $result['raw_response'] ?? null);

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Payment verification failed',
            ];
        }

        try {
            return DB::transaction(function () use ($result, $invoice) {
                $provider = $invoice->provider;
                $subscription = $invoice->subscription;

                // Mark invoice as paid
                $invoice->markAsPaid(
                    $result['transaction_id'],
                    $result['raw_response'] ?? null
                );

                // Store card token for future payments
                $cardToken = $result['token'] ?? null;
                if ($cardToken) {
                    $this->storePaymentMethod(
                        $provider,
                        $cardToken,
                        $result['card_brand'] ?? null,
                        $result['card_last_four'] ?? null,
                        $result['card_expiry'] ?? null
                    );
                }

                // Create recurring profile with PowerTranz
                $tier = SubscriptionTier::from($invoice->tier->value);
                $profileResult = null;
                if ($cardToken) {
                    $profileResult = $this->gateway->createRecurringProfile(
                        token: $cardToken,
                        amount: $invoice->amount,
                        currency: $invoice->currency,
                        billingCycle: $invoice->billing_cycle,
                        startDate: $invoice->period_end,
                        orderId: 'REC-' . strtoupper(Str::random(12)),
                        description: "Zeen Connect {$tier->label()} Subscription"
                    );
                }

                // Upgrade subscription
                $subscription->upgradeTo(
                    $tier,
                    $invoice->billing_cycle,
                    $profileResult['profile_id'] ?? null
                );

                Log::info('Subscription upgraded successfully', [
                    'provider_id' => $provider->id,
                    'tier' => $tier->value,
                    'billing_cycle' => $invoice->billing_cycle,
                    'invoice_id' => $invoice->id,
                ]);

                return [
                    'success' => true,
                    'subscription' => $subscription->fresh(),
                    'invoice' => $invoice->fresh(),
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to complete subscription upgrade', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while activating your subscription. Please contact support.',
            ];
        }
    }

    /**
     * Store a payment method for a provider.
     */
    protected function storePaymentMethod(
        Provider $provider,
        string $token,
        ?string $cardBrand,
        ?string $cardLastFour,
        ?string $cardExpiry
    ): ProviderPaymentMethod {
        // Set all existing payment methods as non-default
        ProviderPaymentMethod::where('provider_id', $provider->id)
            ->update(['is_default' => false]);

        // Create new payment method as default
        return ProviderPaymentMethod::create([
            'provider_id' => $provider->id,
            'powertranz_token' => $token,
            'card_brand' => $cardBrand,
            'card_last_four' => $cardLastFour ?? '****',
            'card_expiry' => $cardExpiry ?? 'N/A',
            'is_default' => true,
        ]);
    }

    /**
     * Start a free trial for a provider.
     */
    public function startTrial(Provider $provider, SubscriptionTier $tier, int $days = 14): array
    {
        $subscription = $provider->subscription;

        if (! $subscription->canStartTrial()) {
            return [
                'success' => false,
                'error' => $subscription->has_used_trial
                    ? 'You have already used your free trial.'
                    : 'Trial is only available for Starter tier.',
            ];
        }

        if (! $tier->isPaid()) {
            return [
                'success' => false,
                'error' => 'Trial is only available for paid tiers.',
            ];
        }

        $subscription->startTrial($tier, $days);

        Log::info('Trial started', [
            'provider_id' => $provider->id,
            'tier' => $tier->value,
            'days' => $days,
        ]);

        return [
            'success' => true,
            'subscription' => $subscription->fresh(),
            'trial_ends_at' => $subscription->trial_ends_at,
        ];
    }

    /**
     * Cancel a subscription.
     * Subscription remains active until end of billing period.
     */
    public function cancelSubscription(Provider $provider, ?string $reason = null): array
    {
        $subscription = $provider->subscription;

        if ($subscription->tier->isFree()) {
            return [
                'success' => false,
                'error' => 'Cannot cancel a free subscription.',
            ];
        }

        if ($subscription->isCancelled()) {
            return [
                'success' => false,
                'error' => 'Subscription is already cancelled.',
            ];
        }

        try {
            // Cancel recurring profile with PowerTranz if exists
            if ($subscription->powertranz_profile_id) {
                $this->gateway->cancelRecurringProfile($subscription->powertranz_profile_id);
            }

            // Mark subscription as cancelled
            $subscription->cancel();

            Log::info('Subscription cancelled', [
                'provider_id' => $provider->id,
                'tier' => $subscription->tier->value,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'subscription' => $subscription->fresh(),
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'provider_id' => $provider->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An error occurred while cancelling your subscription. Please contact support.',
            ];
        }
    }

    /**
     * Reactivate a cancelled subscription (before grace period ends).
     */
    public function reactivateSubscription(Provider $provider): array
    {
        $subscription = $provider->subscription;

        if (! $subscription->isCancelled()) {
            return [
                'success' => false,
                'error' => 'Subscription is not cancelled.',
            ];
        }

        if (! $subscription->isInGracePeriod()) {
            return [
                'success' => false,
                'error' => 'Grace period has ended. Please upgrade again.',
            ];
        }

        // Reactivate subscription
        $subscription->reactivate();

        // Re-create recurring profile if we have a payment method
        $paymentMethod = ProviderPaymentMethod::where('provider_id', $provider->id)
            ->where('is_default', true)
            ->first();

        if ($paymentMethod) {
            $profileResult = $this->gateway->createRecurringProfile(
                token: $paymentMethod->powertranz_token,
                amount: $subscription->getCurrentPrice(),
                currency: 'JMD',
                billingCycle: $subscription->billing_cycle,
                startDate: $subscription->expires_at,
                orderId: 'REC-' . strtoupper(Str::random(12)),
                description: "Zeen Connect {$subscription->tier->label()} Subscription"
            );

            if ($profileResult['profile_id']) {
                $subscription->update(['powertranz_profile_id' => $profileResult['profile_id']]);
            }
        }

        Log::info('Subscription reactivated', [
            'provider_id' => $provider->id,
            'tier' => $subscription->tier->value,
        ]);

        return [
            'success' => true,
            'subscription' => $subscription->fresh(),
        ];
    }

    /**
     * Process a renewal payment (for manual recurring mode).
     */
    public function processRenewal(Subscription $subscription): array
    {
        $provider = $subscription->provider;
        $paymentMethod = ProviderPaymentMethod::where('provider_id', $provider->id)
            ->where('is_default', true)
            ->first();

        if (! $paymentMethod) {
            return [
                'success' => false,
                'error' => 'No payment method on file.',
            ];
        }

        $amount = $subscription->getCurrentPrice();
        $orderId = 'REN-' . strtoupper(Str::random(12));

        // Create invoice
        $invoice = SubscriptionInvoice::create([
            'subscription_id' => $subscription->id,
            'provider_id' => $provider->id,
            'invoice_number' => SubscriptionInvoice::generateInvoiceNumber(),
            'tier' => $subscription->tier->value,
            'billing_cycle' => $subscription->billing_cycle,
            'amount' => $amount,
            'currency' => 'JMD',
            'status' => InvoiceStatus::PENDING,
            'period_start' => $subscription->expires_at,
            'period_end' => $subscription->billing_cycle === 'annual'
                ? $subscription->expires_at->addYear()
                : $subscription->expires_at->addMonth(),
            'powertranz_transaction_id' => $orderId,
        ]);

        // Charge the stored card token
        $result = $this->gateway->chargeToken(
            token: $paymentMethod->powertranz_token,
            amount: $amount,
            currency: 'JMD',
            orderId: $orderId,
            description: "Zeen Connect {$subscription->tier->label()} Subscription Renewal"
        );

        if ($result['success']) {
            $invoice->markAsPaid($result['transaction_id'], $result['raw_response'] ?? null);
            $subscription->extendSubscription();

            Log::info('Subscription renewed successfully', [
                'subscription_id' => $subscription->id,
                'provider_id' => $provider->id,
                'invoice_id' => $invoice->id,
            ]);

            return [
                'success' => true,
                'invoice' => $invoice->fresh(),
                'subscription' => $subscription->fresh(),
            ];
        }

        $invoice->markAsFailed($result['error'] ?? 'Payment failed', $result['raw_response'] ?? null);

        Log::warning('Subscription renewal failed', [
            'subscription_id' => $subscription->id,
            'provider_id' => $provider->id,
            'invoice_id' => $invoice->id,
            'error' => $result['error'],
        ]);

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Payment failed',
            'invoice' => $invoice->fresh(),
        ];
    }

    /**
     * Initialize payment method update.
     */
    public function initiatePaymentMethodUpdate(Provider $provider, string $returnUrl): array
    {
        $user = $provider->user;
        $orderId = 'PMU-' . strtoupper(Str::random(12));

        $result = $this->gateway->initializePaymentMethodUpdate(
            returnUrl: $returnUrl . '?order=' . $orderId,
            email: $user->email,
            name: $user->name,
            orderId: $orderId
        );

        return $result;
    }

    /**
     * Complete payment method update after 3DS.
     */
    public function completePaymentMethodUpdate(string $spiToken, Provider $provider): array
    {
        $result = $this->gateway->verifySubscriptionPayment($spiToken);

        if (! $result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Card verification failed',
            ];
        }

        $cardToken = $result['token'] ?? null;
        if (! $cardToken) {
            return [
                'success' => false,
                'error' => 'Failed to tokenize card. Please try again.',
            ];
        }

        $paymentMethod = $this->storePaymentMethod(
            $provider,
            $cardToken,
            $result['card_brand'] ?? null,
            $result['card_last_four'] ?? null,
            $result['card_expiry'] ?? null
        );

        // Update recurring profile if subscription has one
        $subscription = $provider->subscription;
        if ($subscription->powertranz_profile_id) {
            // Cancel old profile and create new one with new card
            $this->gateway->cancelRecurringProfile($subscription->powertranz_profile_id);

            $profileResult = $this->gateway->createRecurringProfile(
                token: $cardToken,
                amount: $subscription->getCurrentPrice(),
                currency: 'JMD',
                billingCycle: $subscription->billing_cycle,
                startDate: $subscription->expires_at,
                orderId: 'REC-' . strtoupper(Str::random(12)),
                description: "Zeen Connect {$subscription->tier->label()} Subscription"
            );

            $subscription->update(['powertranz_profile_id' => $profileResult['profile_id'] ?? null]);
        }

        Log::info('Payment method updated', [
            'provider_id' => $provider->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        return [
            'success' => true,
            'payment_method' => $paymentMethod,
        ];
    }

    /**
     * Get the default payment method for a provider.
     */
    public function getDefaultPaymentMethod(Provider $provider): ?ProviderPaymentMethod
    {
        return ProviderPaymentMethod::where('provider_id', $provider->id)
            ->where('is_default', true)
            ->first();
    }

    /**
     * Get all invoices for a provider.
     */
    public function getInvoices(Provider $provider, int $perPage = 15)
    {
        return SubscriptionInvoice::where('provider_id', $provider->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
