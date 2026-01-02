<?php

namespace Database\Factories;

use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\SubscriptionStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'provider_id' => Provider::factory(),
            'tier' => SubscriptionTier::STARTER,
            'status' => SubscriptionStatus::ACTIVE,
            'billing_cycle' => 'monthly',
            'trial_ends_at' => null,
            'started_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'expires_at' => null,
            'cancelled_at' => null,
            'grace_period_ends_at' => null,
            'has_used_trial' => false,
            'stripe_subscription_id' => null,
            'powertranz_profile_id' => null,
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Tier States
    // =========================================================================

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => SubscriptionTier::STARTER,
            'billing_cycle' => 'monthly',
            'expires_at' => null,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => SubscriptionTier::PREMIUM,
            'billing_cycle' => fake()->randomElement(['monthly', 'annual']),
            'expires_at' => now()->addYear(),
            'powertranz_profile_id' => 'sandbox_' . Str::random(16),
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'tier' => SubscriptionTier::ENTERPRISE,
            'billing_cycle' => fake()->randomElement(['monthly', 'annual']),
            'expires_at' => now()->addYear(),
            'powertranz_profile_id' => 'sandbox_' . Str::random(16),
        ]);
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'tier' => SubscriptionTier::PREMIUM,
            'trial_ends_at' => now()->addDays(fake()->numberBetween(1, 14)),
            'has_used_trial' => true,
        ]);
    }

    public function trialExpired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'tier' => SubscriptionTier::STARTER,
            'trial_ends_at' => now()->subDays(fake()->numberBetween(1, 30)),
            'has_used_trial' => true,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::CANCELLED,
            'cancelled_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    public function inGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'cancelled_at' => now()->subDays(5),
            'grace_period_ends_at' => now()->addDays(fake()->numberBetween(5, 25)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED,
            'expires_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    // =========================================================================
    // Billing States
    // =========================================================================

    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'monthly',
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_cycle' => 'annual',
            'expires_at' => now()->addYear(),
        ]);
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }
}
