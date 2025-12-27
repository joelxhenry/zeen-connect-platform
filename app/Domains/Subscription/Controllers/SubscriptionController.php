<?php

namespace App\Domains\Subscription\Controllers;

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
            'allTiers' => $this->getAllTiersComparison(),
            'subscription' => $subscription ? [
                'started_at' => $subscription->started_at?->format('M j, Y'),
                'expires_at' => $subscription->expires_at?->format('M j, Y'),
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
            ] : null,
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
    protected function getAllTiersComparison(): array
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
                'monthly_price_display' => $this->formatPrice($tier->monthlyPrice()),
                'deposit_percentage' => $tier->depositPercentage(),
                'zeen_fee_rate' => $zeenFeeRate,
                'gateway_fee_rate' => $gatewayFeeRate,
                'total_fee_rate' => $totalFeeRate,
                'platform_fee_rate' => $totalFeeRate, // Legacy alias
                'team_slots' => $teamSlots === PHP_INT_MAX ? 'unlimited' : $teamSlots,
                'team_description' => $this->subscriptionService->getTeamMemberLimitDescription($tier),
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
