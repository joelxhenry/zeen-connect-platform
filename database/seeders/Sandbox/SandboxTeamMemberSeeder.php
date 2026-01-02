<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;

class SandboxTeamMemberSeeder extends Seeder
{
    /**
     * Seed team members for providers that support teams.
     */
    public function run(): void
    {
        // Get providers with their subscriptions (only premium and enterprise support teams)
        $providers = Provider::with('subscription')
            ->whereHas('subscription', function ($query) {
                $query->whereIn('tier', [
                    SubscriptionTier::PREMIUM->value,
                    SubscriptionTier::ENTERPRISE->value,
                ]);
            })
            ->where('status', 'active')
            ->get();

        foreach ($providers as $provider) {
            $tier = $provider->subscription?->tier ?? SubscriptionTier::STARTER;

            // Skip starter tier (no team support)
            if ($tier === SubscriptionTier::STARTER) {
                continue;
            }

            // Determine team member count based on tier
            $memberCount = match ($tier) {
                SubscriptionTier::PREMIUM => fake()->numberBetween(1, 5),
                SubscriptionTier::ENTERPRISE => fake()->numberBetween(5, 15),
                default => 0,
            };

            $this->createTeamMembersForProvider($provider, $memberCount);
        }
    }

    protected function createTeamMembersForProvider(Provider $provider, int $count): void
    {
        // Permission presets distribution: 30% viewer, 40% staff, 30% admin
        $presetWeights = [
            'viewer' => 30,
            'staff' => 40,
            'admin' => 30,
        ];

        // Status distribution: 70% active, 20% pending, 10% suspended
        $statusWeights = [
            'active' => 70,
            'pending' => 20,
            'suspended' => 10,
        ];

        for ($i = 0; $i < $count; $i++) {
            $preset = $this->getWeighted($presetWeights);
            $status = $this->getWeighted($statusWeights);

            // For active members, create a user
            $user = null;
            if ($status === 'active') {
                $user = User::factory()->create([
                    'role' => 'client',
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
            }

            TeamMember::factory()
                ->forProvider($provider)
                ->state(function ($attributes) use ($user, $status, $preset) {
                    $state = [
                        'status' => TeamMemberStatus::from($status),
                    ];

                    if ($user) {
                        $state['user_id'] = $user->id;
                        $state['email'] = $user->email;
                        $state['name'] = $user->name;
                        $state['invitation_token'] = null;
                        $state['accepted_at'] = now()->subDays(fake()->numberBetween(1, 60));
                    }

                    return $state;
                })
                ->{$preset}()
                ->create();
        }
    }

    protected function getWeighted(array $weights): string
    {
        $rand = fake()->numberBetween(1, 100);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
