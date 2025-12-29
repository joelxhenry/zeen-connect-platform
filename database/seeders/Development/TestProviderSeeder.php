<?php

namespace Database\Seeders\Development;

use App\Domains\Subscription\Enums\SubscriptionStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestProviderSeeder extends Seeder
{
    /**
     * Seed test providers - one for each subscription tier.
     */
    public function run(): void
    {
        $providers = $this->getProviders();
        $now = now();

        foreach ($providers as $providerData) {
            // 1. Create or update the user
            $existingUser = DB::table('users')->where('email', $providerData['email'])->first();
            $userId = $existingUser?->id;

            if (! $userId) {
                $userId = DB::table('users')->insertGetId([
                    'uuid' => Str::uuid()->toString(),
                    'name' => $providerData['name'],
                    'email' => $providerData['email'],
                    'password' => Hash::make('password'),
                    'role' => 'provider',
                    'email_verified_at' => $now,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'name' => $providerData['name'],
                    'role' => 'provider',
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            }

            // 2. Create or update the provider
            $existingProvider = DB::table('providers')->where('user_id', $userId)->first();
            $providerId = $existingProvider?->id;

            if (! $providerId) {
                $providerId = DB::table('providers')->insertGetId([
                    'uuid' => Str::uuid()->toString(),
                    'user_id' => $userId,
                    'business_name' => $providerData['business_name'],
                    'slug' => $providerData['slug'],
                    'domain' => $providerData['domain'],
                    'bio' => $providerData['bio'],
                    'tagline' => $providerData['tagline'],
                    'status' => 'active',
                    'is_featured' => $providerData['is_featured'],
                    'is_founding_member' => $providerData['is_founding_member'],
                    'founding_member_at' => $providerData['is_founding_member'] ? $now : null,
                    'verified_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('providers')->where('id', $providerId)->update([
                    'business_name' => $providerData['business_name'],
                    'bio' => $providerData['bio'],
                    'tagline' => $providerData['tagline'],
                    'status' => 'active',
                    'is_featured' => $providerData['is_featured'],
                    'is_founding_member' => $providerData['is_founding_member'],
                    'founding_member_at' => $providerData['is_founding_member'] ? $now : null,
                    'updated_at' => $now,
                ]);
            }

            // 3. Create or update the subscription
            $existingSubscription = DB::table('subscriptions')
                ->where('provider_id', $providerId)
                ->first();

            if (! $existingSubscription) {
                DB::table('subscriptions')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'provider_id' => $providerId,
                    'tier' => $providerData['tier'],
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'started_at' => $now,
                    'expires_at' => $providerData['tier'] === SubscriptionTier::STARTER->value
                        ? null
                        : $now->copy()->addYear(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                DB::table('subscriptions')->where('id', $existingSubscription->id)->update([
                    'tier' => $providerData['tier'],
                    'status' => SubscriptionStatus::ACTIVE->value,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info('Test providers seeded: '.count($providers).' providers (one per tier)');
    }

    private function getProviders(): array
    {
        return [
            // Starter Tier Provider
            [
                'name' => 'Sarah Starter',
                'email' => 'starter@example.com',
                'business_name' => 'Starter Styles Salon',
                'slug' => 'starter-styles-salon',
                'domain' => 'starter-styles',
                'bio' => 'A friendly neighborhood salon offering quality haircuts and styling at affordable prices. Perfect for those getting started with professional grooming services.',
                'tagline' => 'Quality styling, affordable prices',
                'tier' => SubscriptionTier::STARTER->value,
                'is_featured' => false,
                'is_founding_member' => false,
            ],
            // Premium Tier Provider
            [
                'name' => 'Patricia Premium',
                'email' => 'premium@example.com',
                'business_name' => 'Premium Wellness Spa',
                'slug' => 'premium-wellness-spa',
                'domain' => 'premium-wellness',
                'bio' => 'An upscale wellness spa offering a full range of beauty and relaxation services. Our team of skilled professionals is dedicated to helping you look and feel your best.',
                'tagline' => 'Elevate your wellness experience',
                'tier' => SubscriptionTier::PREMIUM->value,
                'is_featured' => true,
                'is_founding_member' => true,
            ],
            // Enterprise Tier Provider
            [
                'name' => 'Emily Enterprise',
                'email' => 'enterprise@example.com',
                'business_name' => 'Enterprise Beauty Group',
                'slug' => 'enterprise-beauty-group',
                'domain' => 'enterprise-beauty',
                'bio' => 'A premier multi-location beauty enterprise offering comprehensive services across the island. With our enterprise solutions, we provide seamless booking experiences for large-scale operations.',
                'tagline' => 'Beauty at scale',
                'tier' => SubscriptionTier::ENTERPRISE->value,
                'is_featured' => true,
                'is_founding_member' => true,
            ],
        ];
    }
}
