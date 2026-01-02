<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;
use Database\Factories\ServiceFactory;
use Illuminate\Database\Seeder;

class SandboxServiceSeeder extends Seeder
{
    /**
     * Seed services for each provider.
     */
    public function run(): void
    {
        // Get all providers with their subscriptions
        $providers = Provider::with('subscription')->get();

        foreach ($providers as $provider) {
            $tier = $provider->subscription?->tier ?? SubscriptionTier::STARTER;

            // Determine service count based on tier
            $serviceCount = match ($tier) {
                SubscriptionTier::STARTER => fake()->numberBetween(2, 4),
                SubscriptionTier::PREMIUM => fake()->numberBetween(4, 8),
                SubscriptionTier::ENTERPRISE => fake()->numberBetween(8, 15),
            };

            // Create services for this provider
            $this->createServicesForProvider($provider, $serviceCount);
        }
    }

    protected function createServicesForProvider(Provider $provider, int $count): void
    {
        $categories = ['haircut', 'color', 'styling', 'treatment', 'nails', 'spa', 'barber', 'makeup', 'waxing'];
        $usedServices = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random category
            $category = fake()->randomElement($categories);

            // Create service with factory
            $service = Service::factory()
                ->forProvider($provider)
                ->fromCategory($category)
                ->make();

            // Skip if we already have this service name
            if (isset($usedServices[$service->name])) {
                continue;
            }
            $usedServices[$service->name] = true;

            // Set sort order and save
            $service->sort_order = $i + 1;
            $service->save();
        }
    }
}
