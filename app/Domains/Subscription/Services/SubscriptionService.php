<?php

namespace App\Domains\Subscription\Services;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Services\FeeCalculator;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;

class SubscriptionService
{
    public function __construct(
        protected FeeCalculator $feeCalculator
    ) {}

    // =========================================================================
    // Fee Rate Methods (delegated to FeeCalculator)
    // =========================================================================

    /**
     * Get the gateway fee rate (percentage).
     */
    public function getGatewayFeeRate(): float
    {
        return $this->feeCalculator->getGatewayFeeRate();
    }

    /**
     * Get the Zeen platform fee rate for a provider based on their tier (percentage).
     */
    public function getZeenFeeRate(Provider $provider): float
    {
        return $this->feeCalculator->getZeenFeeRate($provider);
    }

    /**
     * Get the total fee rate for a provider (Zeen + Gateway).
     */
    public function getTotalFeeRate(Provider $provider): float
    {
        return $this->feeCalculator->getTotalFeeRate($provider);
    }

    /**
     * Get the platform fee rate for a provider based on their tier.
     * @deprecated Use getZeenFeeRate() instead. Returns decimal for backwards compatibility.
     */
    public function getPlatformFeeRate(Provider $provider): float
    {
        return $this->getZeenFeeRate($provider) / 100;
    }

    // =========================================================================
    // Deposit Methods (delegated to FeeCalculator)
    // =========================================================================

    /**
     * Get the effective deposit percentage for a provider based on their tier.
     */
    public function getEffectiveDepositPercentage(Provider $provider): float
    {
        return $this->feeCalculator->getEffectiveDepositPercentage($provider);
    }

    /**
     * Get the premium tier deposit percentage, respecting minimum.
     */
    protected function getPremiumDepositPercentage(Provider $provider): float
    {
        $minimum = (float) SystemSetting::get('minimum_deposit_percentage', 15);
        $providerSetting = $provider->deposit_percentage;

        if ($providerSetting === null) {
            return $minimum;
        }

        return max((float) $providerSetting, $minimum);
    }

    /**
     * Calculate the deposit amount based on service settings or provider tier.
     *
     * @return array{amount: float, percentage: float}
     */
    public function calculateDepositAmount(Provider $provider, float $servicePrice, ?Service $service = null): array
    {
        return $this->feeCalculator->calculateDepositAmount($provider, $servicePrice, $service);
    }

    /**
     * Check if a provider's subscription is active.
     */
    public function isSubscriptionActive(Provider $provider): bool
    {
        $subscription = $provider->subscription;

        if (! $subscription) {
            // No subscription means free tier (always active)
            return true;
        }

        return $subscription->isActive();
    }

    /**
     * Create a free tier subscription for a new provider.
     */
    public function createFreeSubscription(Provider $provider): void
    {
        $provider->subscription()->create([
            'tier' => SubscriptionTier::STARTER,
            'started_at' => now(),
        ]);
    }

    /**
     * Calculate no-show deposit distribution.
     */
    public function calculateNoShowSplit(float $depositAmount): array
    {
        $providerPercentage = (float) SystemSetting::get('no_show_deposit_provider_percentage', 50);
        $platformPercentage = (float) SystemSetting::get('no_show_deposit_platform_percentage', 50);

        return [
            'provider_amount' => round($depositAmount * $providerPercentage / 100, 2),
            'platform_amount' => round($depositAmount * $platformPercentage / 100, 2),
            'provider_percentage' => $providerPercentage,
            'platform_percentage' => $platformPercentage,
        ];
    }

    /**
     * Get minimum deposit amount for Free tier.
     */
    public function getMinimumDepositAmount(): float
    {
        return (float) SystemSetting::get('minimum_deposit_amount', 500);
    }

    /**
     * Get tier monthly subscription price.
     */
    public function getTierMonthlyPrice(SubscriptionTier $tier): float
    {
        return $tier->monthlyPrice();
    }

    /**
     * Get the effective monthly price for a provider.
     * Uses founding member locked price if applicable.
     *
     * Note: When a founding member upgrades their tier, their locked price
     * only applies to the tier they originally joined at. If they upgrade
     * to a higher tier, they pay the regular price for that tier.
     */
    public function getEffectiveMonthlyPrice(Provider $provider): float
    {
        // Founding members with locked price pay their locked rate
        // This only applies if they're still on the tier they joined as
        $foundingTier = $provider->getFoundingSubscriptionTier();
        if ($foundingTier !== null) {
            $currentTier = $provider->getTier();

            // Only apply locked price if still on the original founding tier
            if ($currentTier === $foundingTier) {
                return $provider->getFoundingLockedPrice();
            }
        }

        return $provider->getTier()->monthlyPrice();
    }

    // =========================================================================
    // Team Member Fee Methods
    // =========================================================================

    /**
     * Get the monthly fee for each additional team member beyond free slots.
     */
    public function getExtraTeamMemberMonthlyFee(): float
    {
        return (float) SystemSetting::get('extra_team_member_monthly_fee', 1000);
    }

    /**
     * Get the number of free team member slots for premium tier (from settings).
     */
    public function getPremiumFreeTeamMemberSlots(): int
    {
        return (int) SystemSetting::get('premium_tier_free_team_members', 3);
    }

    /**
     * Calculate team member charges for a provider.
     *
     * @return array{
     *     tier: string,
     *     supports_team: bool,
     *     free_slots: int,
     *     active_count: int,
     *     extra_count: int,
     *     fee_per_extra: float,
     *     total_extra_fee: float,
     *     would_exceed_free_slots: bool
     * }
     */
    public function calculateTeamMemberCharges(Provider $provider): array
    {
        $tier = $provider->getTier();
        $supportsTeam = $tier->supportsTeam();
        $freeSlots = $supportsTeam ? $provider->getFreeTeamMemberSlots() : 0;
        $activeCount = $provider->getTeamMemberCount();
        $extraCount = $provider->getExtraTeamMemberCount();
        $feePerExtra = $this->getExtraTeamMemberMonthlyFee();

        // Enterprise tier has no extra fees
        $totalExtraFee = $tier === SubscriptionTier::ENTERPRISE
            ? 0.0
            : $extraCount * $feePerExtra;

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),
            'supports_team' => $supportsTeam,
            'free_slots' => $freeSlots === PHP_INT_MAX ? -1 : $freeSlots, // -1 indicates unlimited
            'active_count' => $activeCount,
            'extra_count' => $extraCount,
            'fee_per_extra' => $feePerExtra,
            'total_extra_fee' => $totalExtraFee,
            'would_exceed_free_slots' => $provider->wouldExceedFreeSlots(),
        ];
    }

    /**
     * Get a formatted description of team member limits for a tier.
     */
    public function getTeamMemberLimitDescription(SubscriptionTier $tier): string
    {
        return match ($tier) {
            SubscriptionTier::STARTER => 'Team members not available on Free tier',
            SubscriptionTier::PREMIUM => sprintf(
                '%d free members, then ₦%s/month each',
                $this->getPremiumFreeTeamMemberSlots(),
                number_format($this->getExtraTeamMemberMonthlyFee())
            ),
            SubscriptionTier::ENTERPRISE => 'Unlimited team members',
        };
    }

    // =========================================================================
    // Service Restriction Methods
    // =========================================================================

    /**
     * Get the minimum service price for a provider based on their tier.
     */
    public function getMinimumServicePrice(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            SubscriptionTier::STARTER => (float) SystemSetting::get('starter_tier_minimum_service_price', 1000),
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('premium_tier_minimum_service_price', 500),
            SubscriptionTier::ENTERPRISE => (float) SystemSetting::get('enterprise_tier_minimum_service_price', 0),
        };
    }

    /**
     * Get the minimum deposit percentage for a provider based on their tier.
     * This should be at least enough to cover the platform fee.
     */
    public function getMinimumDepositPercentage(Provider $provider): float
    {
        $tier = $provider->getTier();

        return match ($tier) {
            // Starter tier: deposit must cover the platform fee
            SubscriptionTier::STARTER => (float) SystemSetting::get('free_tier_deposit_percentage', 20),
            // Premium tier: minimum deposit percentage from settings
            SubscriptionTier::PREMIUM => (float) SystemSetting::get('minimum_deposit_percentage', 15),
            // Enterprise tier: no minimum required
            SubscriptionTier::ENTERPRISE => 0.0,
        };
    }

    /**
     * Get effective booking settings for a provider, respecting tier restrictions.
     *
     * This method returns what the actual booking settings will be after applying
     * tier-based rules. For example, if a Starter tier provider has deposit_type='none'
     * stored, but their tier enforces a minimum deposit, this returns the enforced values.
     *
     * @return array{
     *     requires_approval: bool,
     *     deposit_type: string,
     *     deposit_amount: float|null,
     *     cancellation_policy: string,
     *     advance_booking_days: int,
     *     min_booking_notice_hours: int
     * }
     */
    public function getEffectiveBookingSettings(Provider $provider): array
    {
        $raw = $provider->getBookingSettings();
        $tier = $provider->getTier();
        $minDepositPercentage = $this->getMinimumDepositPercentage($provider);

        // Determine effective deposit settings based on tier
        $canDisableDeposit = $tier === SubscriptionTier::ENTERPRISE;
        $effectiveDepositType = $raw['deposit_type'];
        $effectiveDepositAmount = $raw['deposit_amount'];

        // If tier cannot disable deposit but provider has 'none', force percentage
        if (! $canDisableDeposit && $effectiveDepositType === 'none') {
            $effectiveDepositType = 'percentage';
            $effectiveDepositAmount = $minDepositPercentage;
        }

        // Ensure deposit amount meets minimum for the tier
        if ($effectiveDepositType === 'percentage' && $minDepositPercentage > 0) {
            $effectiveDepositAmount = max($effectiveDepositAmount ?? 0, $minDepositPercentage);
        }

        return [
            'requires_approval' => $raw['requires_approval'],
            'deposit_type' => $effectiveDepositType,
            'deposit_amount' => $effectiveDepositAmount,
            'cancellation_policy' => $raw['cancellation_policy'],
            'advance_booking_days' => $raw['advance_booking_days'],
            'min_booking_notice_hours' => $raw['min_booking_notice_hours'],
        ];
    }

    /**
     * Get all tier restrictions for frontend display.
     */
    public function getTierRestrictions(Provider $provider): array
    {
        $tier = $provider->getTier();
        $zeenFeeRate = $this->getZeenFeeRate($provider);
        $gatewayFeeRate = $this->getGatewayFeeRate();
        $totalFeeRate = $zeenFeeRate + $gatewayFeeRate;
        $minDepositPercentage = $this->getMinimumDepositPercentage($provider);
        $minServicePrice = $this->getMinimumServicePrice($provider);
        $teamSlots = $tier->teamSlots();

        // Determine monthly price (use locked price for founding members)
        $monthlyPrice = $this->getEffectiveMonthlyPrice($provider);

        return [
            'tier' => $tier->value,
            'tier_label' => $tier->label(),

            // Fee rates
            'zeen_fee_rate' => $zeenFeeRate,
            'gateway_fee_rate' => $gatewayFeeRate,
            'total_fee_rate' => $totalFeeRate,
            'platform_fee_rate' => $totalFeeRate, // Legacy alias

            // Team slots
            'team_slots' => $teamSlots === PHP_INT_MAX ? 'unlimited' : $teamSlots,

            // Monthly price
            'monthly_price' => $monthlyPrice,

            // Service restrictions
            'minimum_service_price' => $minServicePrice,
            'minimum_service_price_display' => $this->formatCurrency($minServicePrice),
            'minimum_deposit_percentage' => $minDepositPercentage,

            // Capabilities
            'can_disable_deposit' => $tier === SubscriptionTier::ENTERPRISE,
            'can_customize_deposit' => $tier !== SubscriptionTier::STARTER,
            'can_pass_fees_to_client' => true, // All tiers can now do this

            // Founding member info
            'is_founding_member' => $provider->isFoundingMember(),
            'has_founding_fee_waiver' => $provider->hasFoundingFeeWaiver(),
            'founding_tier' => $provider->getFoundingSubscriptionTier()?->value,
            'founding_fee_waiver_ends_at' => $provider->getFoundingFeeWaiverEndsAt()?->toDateString(),

            // Help text
            'deposit_help_text' => $this->getDepositHelpText($tier, $minDepositPercentage, $totalFeeRate),
            'price_help_text' => $this->getPriceHelpText($tier, $minServicePrice),
            'fee_help_text' => $this->getFeeHelpText($provider, $tier, $zeenFeeRate, $gatewayFeeRate),
        ];
    }

    /**
     * Get help text explaining fee structure for a tier.
     */
    private function getFeeHelpText(Provider $provider, SubscriptionTier $tier, float $zeenFee, float $gatewayFee): string
    {
        $totalFee = $zeenFee + $gatewayFee;

        // Founding members with active waiver
        if ($provider->hasFoundingFeeWaiver()) {
            $waiverEnds = $provider->getFoundingFeeWaiverEndsAt()->format('M j, Y');

            return sprintf(
                'Founding member: 0%% Zeen fee (waived until %s) + %.1f%% gateway fee = %.1f%% total per transaction.',
                $waiverEnds,
                $gatewayFee,
                $totalFee
            );
        }

        return sprintf(
            '%s tier: %.1f%% Zeen fee + %.1f%% gateway fee = %.1f%% total per transaction.',
            $tier->label(),
            $zeenFee,
            $gatewayFee,
            $totalFee
        );
    }

    /**
     * Get help text explaining deposit restrictions for a tier.
     */
    private function getDepositHelpText(SubscriptionTier $tier, float $minDeposit, float $platformFee): string
    {
        return match ($tier) {
            SubscriptionTier::STARTER => sprintf(
                'Starter tier requires a %d%% deposit to cover the %d%% platform fee.',
                (int) $minDeposit,
                (int) $platformFee
            ),
            SubscriptionTier::PREMIUM => sprintf(
                'Premium tier requires a minimum %d%% deposit. You can set a higher percentage.',
                (int) $minDeposit
            ),
            SubscriptionTier::ENTERPRISE => 'Enterprise tier has no deposit restrictions. You can set any deposit amount or none at all.',
        };
    }

    /**
     * Get help text explaining price restrictions for a tier.
     */
    private function getPriceHelpText(SubscriptionTier $tier, float $minPrice): string
    {
        if ($minPrice <= 0) {
            return 'No minimum service price for your tier.';
        }

        return sprintf(
            'Your %s tier requires a minimum service price of %s.',
            $tier->label(),
            $this->formatCurrency($minPrice)
        );
    }

    /**
     * Format a currency amount for display.
     */
    private function formatCurrency(float $amount): string
    {
        return '$' . number_format($amount, 2);
    }
}
