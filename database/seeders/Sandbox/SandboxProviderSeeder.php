<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Industry\Models\Industry;
use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\SubscriptionStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SandboxProviderSeeder extends Seeder
{
    /**
     * Seed providers with their subscriptions.
     */
    public function run(): void
    {
        $providerCount = config('sandbox.providers', 40);

        // Create the three test providers first (one per tier)
        $this->createTestProviders();

        // Calculate remaining providers to create
        $remainingProviders = max(0, $providerCount - 3);

        if ($remainingProviders > 0) {
            $this->createRandomProviders($remainingProviders);
        }
    }

    protected function createTestProviders(): void
    {
        $testProviders = [
            [
                'email' => 'starter@example.com',
                'name' => 'Sarah Starter',
                'business_name' => 'Starter Styles Salon',
                'tier' => SubscriptionTier::STARTER,
                'is_featured' => false,
                'is_founding_member' => false,
            ],
            [
                'email' => 'premium@example.com',
                'name' => 'Patricia Premium',
                'business_name' => 'Premium Wellness Spa',
                'tier' => SubscriptionTier::PREMIUM,
                'is_featured' => true,
                'is_founding_member' => true,
            ],
            [
                'email' => 'enterprise@example.com',
                'name' => 'Emily Enterprise',
                'business_name' => 'Enterprise Beauty Group',
                'tier' => SubscriptionTier::ENTERPRISE,
                'is_featured' => true,
                'is_founding_member' => true,
            ],
        ];

        $industry = Industry::first();

        foreach ($testProviders as $data) {
            // Create or get the user
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $data['name'],
                    'password' => Hash::make('password'),
                    'role' => 'provider',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );

            // Update role if needed
            if ($user->role !== 'provider') {
                $user->update(['role' => 'provider']);
            }

            // Create or update the provider
            $provider = Provider::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'uuid' => Str::uuid()->toString(),
                    'industry_id' => $industry?->id,
                    'business_name' => $data['business_name'],
                    'slug' => Provider::generateSlug($data['business_name']),
                    'domain' => Provider::generateDomain($data['business_name']),
                    'bio' => fake()->paragraphs(2, true),
                    'tagline' => fake()->sentence(),
                    'status' => 'active',
                    'is_featured' => $data['is_featured'],
                    'is_founding_member' => $data['is_founding_member'],
                    'founding_member_at' => $data['is_founding_member'] ? now()->subMonths(3) : null,
                    'verified_at' => now(),
                ]
            );

            // Create or update the subscription
            Subscription::firstOrCreate(
                ['provider_id' => $provider->id],
                [
                    'uuid' => Str::uuid()->toString(),
                    'tier' => $data['tier'],
                    'status' => SubscriptionStatus::ACTIVE,
                    'billing_cycle' => 'monthly',
                    'started_at' => now()->subMonths(fake()->numberBetween(1, 6)),
                    'expires_at' => $data['tier'] === SubscriptionTier::STARTER
                        ? null
                        : now()->addYear(),
                ]
            );
        }
    }

    protected function createRandomProviders(int $count): void
    {
        // Distribution: 60% starter, 25% premium, 15% enterprise
        $starterCount = (int) ($count * 0.60);
        $premiumCount = (int) ($count * 0.25);
        $enterpriseCount = $count - $starterCount - $premiumCount;

        // Status distribution: 60% active, 20% pending, 15% suspended, 5% inactive
        $statusDistribution = [
            'active' => 60,
            'pending' => 20,
            'suspended' => 15,
            'inactive' => 5,
        ];

        $industries = Industry::all();

        // Create Starter providers
        $this->createProvidersOfTier(SubscriptionTier::STARTER, $starterCount, $statusDistribution, $industries);

        // Create Premium providers
        $this->createProvidersOfTier(SubscriptionTier::PREMIUM, $premiumCount, $statusDistribution, $industries);

        // Create Enterprise providers
        $this->createProvidersOfTier(SubscriptionTier::ENTERPRISE, $enterpriseCount, $statusDistribution, $industries);
    }

    protected function createProvidersOfTier(
        SubscriptionTier $tier,
        int $count,
        array $statusDistribution,
        $industries
    ): void {
        for ($i = 0; $i < $count; $i++) {
            // Determine status based on distribution
            $status = $this->getWeightedStatus($statusDistribution);

            // Create user
            $user = User::factory()->create([
                'role' => 'provider',
                'email_verified_at' => now(),
                'is_active' => $status !== 'inactive',
            ]);

            // Determine if featured (30% of active providers)
            $isFeatured = $status === 'active' && fake()->boolean(30);

            // Determine if founding member (10% of premium/enterprise)
            $isFoundingMember = $tier !== SubscriptionTier::STARTER
                && $status === 'active'
                && fake()->boolean(10);

            // Create provider
            $provider = Provider::factory()
                ->withUser($user)
                ->create([
                    'industry_id' => $industries->random()?->id,
                    'status' => $status,
                    'is_featured' => $isFeatured,
                    'is_founding_member' => $isFoundingMember,
                    'founding_member_at' => $isFoundingMember ? now()->subMonths(fake()->numberBetween(1, 6)) : null,
                    'verified_at' => $status !== 'pending' ? now() : null,
                ]);

            // Create subscription
            Subscription::factory()
                ->forProvider($provider)
                ->state(match ($tier) {
                    SubscriptionTier::STARTER => fn ($attrs) => ['tier' => SubscriptionTier::STARTER],
                    SubscriptionTier::PREMIUM => fn ($attrs) => ['tier' => SubscriptionTier::PREMIUM],
                    SubscriptionTier::ENTERPRISE => fn ($attrs) => ['tier' => SubscriptionTier::ENTERPRISE],
                })
                ->create();
        }
    }

    protected function getWeightedStatus(array $distribution): string
    {
        $rand = fake()->numberBetween(1, 100);
        $cumulative = 0;

        foreach ($distribution as $status => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $status;
            }
        }

        return 'active';
    }
}
