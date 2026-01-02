<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\ProviderAvailability;
use Database\Factories\ProviderAvailabilityFactory;
use Illuminate\Database\Seeder;

class SandboxAvailabilitySeeder extends Seeder
{
    /**
     * Seed availability schedules for all providers.
     */
    public function run(): void
    {
        // Get all active providers
        $providers = Provider::where('status', 'active')->get();

        // Available patterns
        $patterns = ['standard', 'extended', 'late_start', 'early_start', 'salon'];

        foreach ($providers as $provider) {
            // Skip if provider already has availability
            if ($provider->availability()->exists()) {
                continue;
            }

            // Pick a random pattern for this provider
            $pattern = fake()->randomElement($patterns);

            // Create weekly availability
            $this->createWeeklyAvailability($provider, $pattern);
        }
    }

    protected function createWeeklyAvailability(Provider $provider, string $pattern): void
    {
        $patterns = [
            'standard' => [
                'start' => '09:00:00',
                'end' => '17:00:00',
                'available_days' => [1, 2, 3, 4, 5], // Mon-Fri
            ],
            'extended' => [
                'start' => '08:00:00',
                'end' => '20:00:00',
                'available_days' => [1, 2, 3, 4, 5, 6], // Mon-Sat
            ],
            'late_start' => [
                'start' => '10:00:00',
                'end' => '18:00:00',
                'available_days' => [1, 2, 3, 4, 5], // Mon-Fri
            ],
            'early_start' => [
                'start' => '07:00:00',
                'end' => '16:00:00',
                'available_days' => [1, 2, 3, 4, 5], // Mon-Fri
            ],
            'salon' => [
                'start' => '09:00:00',
                'end' => '19:00:00',
                'available_days' => [1, 2, 3, 4, 5, 6], // Mon-Sat
            ],
        ];

        $config = $patterns[$pattern] ?? $patterns['standard'];

        // Create availability for each day of the week
        for ($day = 0; $day <= 6; $day++) {
            $isAvailable = in_array($day, $config['available_days']);

            ProviderAvailability::create([
                'provider_id' => $provider->id,
                'day_of_week' => $day,
                'start_time' => $config['start'],
                'end_time' => $config['end'],
                'is_available' => $isAvailable,
            ]);
        }
    }
}
