<?php

namespace App\Domains\Subscription\Controllers;

use App\Domains\Subscription\Enums\SubscriptionFeature;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\SubscriptionInvoice;
use App\Domains\Subscription\Services\SubscriptionPaymentService;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        protected SubscriptionPaymentService $paymentService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Show the upgrade page with tier selection.
     */
    public function upgrade(): Response
    {
        $provider = Auth::user()->provider;
        $currentTier = $provider->getTier();
        $subscription = $provider->subscription;
        $gatewayFeeRate = $this->subscriptionService->getGatewayFeeRate();

        // Get available tiers for upgrade (paid tiers only)
        $upgradeTiers = [];
        foreach (SubscriptionTier::cases() as $tier) {
            if ($tier->isFree()) {
                continue;
            }

            // Check if this is an upgrade from current tier
            $isUpgrade = $this->isUpgrade($currentTier, $tier);
            $isCurrentTier = $currentTier === $tier;

            $tierFeatures = $tier->features();
            $zeenFeeRate = $tier->zeenFeeRate();

            $upgradeTiers[] = [
                'value' => $tier->value,
                'label' => $tier->label(),
                'color' => $tier->color(),
                'is_upgrade' => $isUpgrade,
                'is_current' => $isCurrentTier,
                'monthly_price' => $tier->monthlyPrice(),
                'monthly_price_display' => $this->formatPrice($tier->monthlyPrice()),
                'annual_price' => $tier->annualPrice(),
                'annual_price_display' => $this->formatPrice($tier->annualPrice()),
                'annual_savings' => $tier->annualSavings(),
                'annual_savings_display' => $this->formatPrice($tier->annualSavings()),
                'annual_savings_percentage' => $tier->annualSavingsPercentage(),
                'effective_monthly_price' => $tier->effectiveMonthlyPrice(),
                'effective_monthly_price_display' => $this->formatPrice($tier->effectiveMonthlyPrice()),
                'zeen_fee_rate' => $zeenFeeRate,
                'gateway_fee_rate' => $gatewayFeeRate,
                'total_fee_rate' => $zeenFeeRate + $gatewayFeeRate,
                'team_slots' => $tier->teamSlots() === PHP_INT_MAX ? 'unlimited' : $tier->teamSlots(),
                'features' => array_map(fn ($f) => [
                    'value' => $f->value,
                    'label' => $f->label(),
                    'icon' => $f->icon(),
                    'available' => in_array($f, $tierFeatures, true),
                ], SubscriptionFeature::cases()),
            ];
        }

        return Inertia::render('Provider/Subscription/Upgrade', [
            'currentTier' => [
                'value' => $currentTier->value,
                'label' => $currentTier->label(),
            ],
            'availableTiers' => $upgradeTiers,
            'canStartTrial' => $subscription?->canStartTrial() ?? true,
            'isOnTrial' => $subscription?->isOnTrial() ?? false,
            'trialDaysRemaining' => $subscription?->trialDaysRemaining() ?? 0,
        ]);
    }

    /**
     * Process upgrade payment.
     */
    public function processUpgrade(Request $request): RedirectResponse
    {
        $request->validate([
            'tier' => ['required', 'string', 'in:premium,enterprise'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
        ]);

        $provider = Auth::user()->provider;
        $tier = SubscriptionTier::from($request->tier);
        $billingCycle = $request->billing_cycle;

        $returnUrl = route('provider.subscription.upgrade.callback');

        $result = $this->paymentService->initiateUpgrade(
            $provider,
            $tier,
            $billingCycle,
            $returnUrl
        );

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.upgrade')
                ->with('error', $result['error'] ?? 'Failed to initiate payment.');
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Handle payment callback after 3DS.
     */
    public function upgradeCallback(Request $request): RedirectResponse
    {
        $invoiceUuid = $request->query('invoice');
        $spiToken = $request->query('SpiToken');

        if (! $invoiceUuid || ! $spiToken) {
            return redirect()
                ->route('provider.subscription.index')
                ->with('error', 'Invalid payment callback.');
        }

        $invoice = SubscriptionInvoice::where('uuid', $invoiceUuid)->first();

        if (! $invoice) {
            return redirect()
                ->route('provider.subscription.index')
                ->with('error', 'Invoice not found.');
        }

        // Verify this invoice belongs to the current provider
        $provider = Auth::user()->provider;
        if ($invoice->provider_id !== $provider->id) {
            return redirect()
                ->route('provider.subscription.index')
                ->with('error', 'Unauthorized.');
        }

        $result = $this->paymentService->completeUpgrade($spiToken, $invoice);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.upgrade')
                ->with('error', $result['error'] ?? 'Payment failed. Please try again.');
        }

        return redirect()
            ->route('provider.subscription.index')
            ->with('success', 'Subscription upgraded successfully!');
    }

    /**
     * Start a free trial.
     */
    public function startTrial(Request $request): RedirectResponse
    {
        $request->validate([
            'tier' => ['required', 'string', 'in:premium,enterprise'],
        ]);

        $provider = Auth::user()->provider;
        $tier = SubscriptionTier::from($request->tier);

        $result = $this->paymentService->startTrial($provider, $tier);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.upgrade')
                ->with('error', $result['error'] ?? 'Failed to start trial.');
        }

        return redirect()
            ->route('provider.subscription.index')
            ->with('success', 'Your 14-day free trial has started!');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $provider = Auth::user()->provider;
        $result = $this->paymentService->cancelSubscription($provider, $request->reason);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.index')
                ->with('error', $result['error'] ?? 'Failed to cancel subscription.');
        }

        $gracePeriodEnd = $result['grace_period_ends_at']?->format('M j, Y');

        return redirect()
            ->route('provider.subscription.index')
            ->with('success', "Subscription cancelled. You can continue using premium features until {$gracePeriodEnd}.");
    }

    /**
     * Reactivate a cancelled subscription.
     */
    public function reactivate(): RedirectResponse
    {
        $provider = Auth::user()->provider;
        $result = $this->paymentService->reactivateSubscription($provider);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.index')
                ->with('error', $result['error'] ?? 'Failed to reactivate subscription.');
        }

        return redirect()
            ->route('provider.subscription.index')
            ->with('success', 'Subscription reactivated successfully!');
    }

    /**
     * Show billing history.
     */
    public function invoices(): Response
    {
        $provider = Auth::user()->provider;
        $invoices = $this->paymentService->getInvoices($provider);

        return Inertia::render('Provider/Subscription/Billing', [
            'invoices' => [
                'data' => $invoices->map(fn ($invoice) => [
                    'id' => $invoice->id,
                    'uuid' => $invoice->uuid,
                    'invoice_number' => $invoice->invoice_number,
                    'tier' => $invoice->tier->label(),
                    'billing_cycle' => $invoice->billing_cycle_display,
                    'amount' => $invoice->amount,
                    'amount_display' => $invoice->amount_display,
                    'currency' => $invoice->currency,
                    'status' => $invoice->status->value,
                    'status_label' => $invoice->status->label(),
                    'status_color' => $invoice->status->color(),
                    'period_display' => $invoice->period_display,
                    'paid_at' => $invoice->paid_at?->format('M j, Y'),
                    'created_at' => $invoice->created_at->format('M j, Y'),
                ]),
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'per_page' => $invoices->perPage(),
                'total' => $invoices->total(),
            ],
            'paymentMethod' => $this->getPaymentMethodData($provider),
        ]);
    }

    /**
     * Download invoice PDF.
     */
    public function downloadInvoice(SubscriptionInvoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $provider = Auth::user()->provider;

        if ($invoice->provider_id !== $provider->id) {
            abort(403);
        }

        // TODO: Generate PDF invoice
        // For now, return a simple response
        return response()->json([
            'message' => 'PDF generation not yet implemented.',
            'invoice' => $invoice->invoice_number,
        ]);
    }

    /**
     * Show payment method edit page.
     */
    public function editPaymentMethod(): Response
    {
        $provider = Auth::user()->provider;
        $paymentMethod = $this->paymentService->getDefaultPaymentMethod($provider);

        return Inertia::render('Provider/Subscription/PaymentMethod', [
            'currentPaymentMethod' => $paymentMethod ? [
                'id' => $paymentMethod->id,
                'card_display' => $paymentMethod->card_display,
                'card_brand' => $paymentMethod->card_brand,
                'card_last_four' => $paymentMethod->card_last_four,
                'card_expiry' => $paymentMethod->card_expiry,
                'is_expired' => $paymentMethod->isExpired(),
            ] : null,
        ]);
    }

    /**
     * Initiate payment method update.
     */
    public function updatePaymentMethod(): RedirectResponse
    {
        $provider = Auth::user()->provider;
        $returnUrl = route('provider.subscription.payment-method.callback');

        $result = $this->paymentService->initiatePaymentMethodUpdate($provider, $returnUrl);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.billing')
                ->with('error', $result['error'] ?? 'Failed to update payment method.');
        }

        return redirect()->away($result['redirect_url']);
    }

    /**
     * Handle payment method update callback.
     */
    public function paymentMethodCallback(Request $request): RedirectResponse
    {
        $spiToken = $request->query('SpiToken');

        if (! $spiToken) {
            return redirect()
                ->route('provider.subscription.billing')
                ->with('error', 'Invalid callback.');
        }

        $provider = Auth::user()->provider;
        $result = $this->paymentService->completePaymentMethodUpdate($spiToken, $provider);

        if (! $result['success']) {
            return redirect()
                ->route('provider.subscription.billing')
                ->with('error', $result['error'] ?? 'Failed to update payment method.');
        }

        return redirect()
            ->route('provider.subscription.billing')
            ->with('success', 'Payment method updated successfully!');
    }

    /**
     * Check if moving to a tier is an upgrade from the current tier.
     */
    protected function isUpgrade(SubscriptionTier $current, SubscriptionTier $target): bool
    {
        $tierOrder = [
            SubscriptionTier::STARTER->value => 0,
            SubscriptionTier::PREMIUM->value => 1,
            SubscriptionTier::ENTERPRISE->value => 2,
        ];

        return $tierOrder[$target->value] > $tierOrder[$current->value];
    }

    /**
     * Format price for display.
     */
    protected function formatPrice(float $price): string
    {
        if ($price === 0.0) {
            return 'Free';
        }

        return '$' . number_format($price);
    }

    /**
     * Get payment method data for display.
     */
    protected function getPaymentMethodData($provider): ?array
    {
        $paymentMethod = $this->paymentService->getDefaultPaymentMethod($provider);

        if (! $paymentMethod) {
            return null;
        }

        return [
            'id' => $paymentMethod->id,
            'card_display' => $paymentMethod->card_display,
            'card_brand' => $paymentMethod->card_brand,
            'card_last_four' => $paymentMethod->card_last_four,
            'card_expiry' => $paymentMethod->card_expiry,
            'is_expired' => $paymentMethod->isExpired(),
        ];
    }
}
