<?php

namespace App\Domains\Subscription\Controllers;

use App\Domains\Provider\Models\ProviderPaymentMethod;
use App\Domains\Subscription\Enums\SubscriptionFeature;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Display the subscription management page.
     */
    public function index(): Response
    {
        $provider = Auth::user()->provider;
        $tier = $provider->getTier();
        $subscription = $provider->subscription;

        $zeenFeeRate = $this->subscriptionService->getZeenFeeRate($provider);
        $gatewayFeeRate = $this->subscriptionService->getGatewayFeeRate();

        // Get default payment method
        $paymentMethod = ProviderPaymentMethod::forProvider($provider->id)
            ->default()
            ->first();

        return Inertia::render('Provider/Subscription/Index', [
            'currentTier' => [
                'value' => $tier->value,
                'label' => $tier->label(),
                'color' => $tier->color(),
                'deposit_percentage' => $this->subscriptionService->getEffectiveDepositPercentage($provider),
                'zeen_fee_rate' => $zeenFeeRate,
                'gateway_fee_rate' => $gatewayFeeRate,
                'total_fee_rate' => $zeenFeeRate + $gatewayFeeRate,
                'platform_fee_rate' => $zeenFeeRate + $gatewayFeeRate, // Legacy alias
                'team_description' => $this->subscriptionService->getTeamMemberLimitDescription($tier),
            ],
            'features' => $this->formatFeaturesForTier($tier),
            'allTiers' => $this->getAllTiersComparison($tier->value),
            'subscription' => $subscription ? [
                'started_at' => $subscription->started_at?->format('M j, Y'),
                'expires_at' => $subscription->expires_at?->format('M j, Y'),
                'status' => $subscription->status->value,
                'status_label' => $subscription->status_display,
                'billing_cycle' => $subscription->billing_cycle,
                'billing_cycle_display' => $subscription->billing_cycle_display,
                'is_on_trial' => $subscription->isOnTrial(),
                'trial_days_remaining' => $subscription->trialDaysRemaining(),
                'is_cancelled' => $subscription->isCancelled(),
                'is_in_grace_period' => $subscription->isInGracePeriod(),
                'grace_period_ends_at' => $subscription->grace_period_ends_at?->format('M j, Y'),
                'next_billing_date' => $subscription->getNextBillingDate()?->format('M j, Y'),
            ] : null,
            'paymentMethod' => $paymentMethod ? [
                'uuid' => $paymentMethod->uuid,
                'card_display' => $paymentMethod->card_display,
                'card_last_four' => $paymentMethod->card_last_four,
                'card_expiry' => $paymentMethod->card_expiry,
                'is_expired' => $paymentMethod->isExpired(),
            ] : null,
            'canStartTrial' => $subscription?->canStartTrial() ?? false,
        ]);
    }

    /**
     * Format features for the current tier.
     */
    protected function formatFeaturesForTier(SubscriptionTier $tier): array
    {
        $tierFeatures = $tier->features();

        return array_map(function (SubscriptionFeature $feature) use ($tierFeatures) {
            $isAvailable = in_array($feature, $tierFeatures, true);

            return [
                'value' => $feature->value,
                'label' => $feature->label(),
                'description' => $feature->description(),
                'icon' => $feature->icon(),
                'available' => $isAvailable,
                'minimum_tier' => $feature->minimumTier()->label(),
            ];
        }, SubscriptionFeature::cases());
    }

    /**
     * Get comparison data for all tiers.
     */
    protected function getAllTiersComparison(string $currentTierValue = null): array
    {
        $tiers = [];
        $gatewayFeeRate = $this->subscriptionService->getGatewayFeeRate();

        foreach (SubscriptionTier::cases() as $tier) {
            $tierFeatures = $tier->features();
            $zeenFeeRate = $tier->zeenFeeRate();
            $totalFeeRate = $zeenFeeRate + $gatewayFeeRate;
            $teamSlots = $tier->teamSlots();

            $tiers[] = [
                'value' => $tier->value,
                'label' => $tier->label(),
                'color' => $tier->color(),
                'monthly_price' => $tier->monthlyPrice(),
                'monthly_price_display' => $tier->monthlyPriceDisplay(),
                'annual_price' => $tier->annualPrice(),
                'annual_price_display' => $tier->annualPriceDisplay(),
                'annual_savings' => $tier->annualSavings(),
                'annual_savings_display' => $this->formatPrice($tier->annualSavings()),
                'annual_savings_percentage' => $tier->annualSavingsPercentage(),
                'effective_monthly_price' => $tier->effectiveMonthlyPrice(),
                'effective_monthly_price_display' => $tier->effectiveMonthlyPriceDisplay(),
                'deposit_percentage' => $tier->depositPercentage(),
                'zeen_fee_rate' => $zeenFeeRate,
                'gateway_fee_rate' => $gatewayFeeRate,
                'total_fee_rate' => $totalFeeRate,
                'platform_fee_rate' => $totalFeeRate, // Legacy alias
                'team_slots' => $teamSlots === PHP_INT_MAX ? 'unlimited' : $teamSlots,
                'team_description' => $this->subscriptionService->getTeamMemberLimitDescription($tier),
                'is_free' => $tier->isFree(),
                'is_current' => $currentTierValue === $tier->value,
                'features' => array_map(function (SubscriptionFeature $feature) use ($tierFeatures) {
                    return [
                        'value' => $feature->value,
                        'label' => $feature->label(),
                        'available' => in_array($feature, $tierFeatures, true),
                    ];
                }, SubscriptionFeature::cases()),
            ];
        }

        return $tiers;
    }

    /**
     * Format price for display.
     */
    protected function formatPrice(float $price): string
    {
        if ($price === 0.0) {
            return 'Free';
        }

        return '₦' . number_format($price);
    }
}
