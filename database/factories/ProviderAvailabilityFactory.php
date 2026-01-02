<?php

namespace Database\Factories;

use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\ProviderAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderAvailability>
 */
class ProviderAvailabilityFactory extends Factory
{
    protected $model = ProviderAvailability::class;

    /**
     * Common business hours patterns.
     */
    protected array $businessHoursPatterns = [
        'standard' => [
            'start' => '09:00:00',
            'end' => '17:00:00',
            'weekdays_only' => true,
        ],
        'extended' => [
            'start' => '08:00:00',
            'end' => '20:00:00',
            'weekdays_only' => false,
        ],
        'late_start' => [
            'start' => '10:00:00',
            'end' => '18:00:00',
            'weekdays_only' => true,
        ],
        'early_start' => [
            'start' => '07:00:00',
            'end' => '16:00:00',
            'weekdays_only' => true,
        ],
        'salon' => [
            'start' => '09:00:00',
            'end' => '19:00:00',
            'weekdays_only' => false,
        ],
    ];

    public function definition(): array
    {
        return [
            'provider_id' => Provider::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Day of Week States
    // =========================================================================

    public function sunday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 0,
            'is_available' => false, // Usually closed on Sunday
        ]);
    }

    public function monday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 1,
        ]);
    }

    public function tuesday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 2,
        ]);
    }

    public function wednesday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 3,
        ]);
    }

    public function thursday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 4,
        ]);
    }

    public function friday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 5,
        ]);
    }

    public function saturday(): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => 6,
        ]);
    }

    public function forDay(int $dayOfWeek): static
    {
        return $this->state(fn (array $attributes) => [
            'day_of_week' => $dayOfWeek,
        ]);
    }

    // =========================================================================
    // Availability States
    // =========================================================================

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    public function closed(): static
    {
        return $this->unavailable();
    }

    // =========================================================================
    // Time States
    // =========================================================================

    public function standardHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'is_available' => true,
        ]);
    }

    public function extendedHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'is_available' => true,
        ]);
    }

    public function morningOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '06:00:00',
            'end_time' => '12:00:00',
            'is_available' => true,
        ]);
    }

    public function afternoonOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '12:00:00',
            'end_time' => '18:00:00',
            'is_available' => true,
        ]);
    }

    public function eveningOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '16:00:00',
            'end_time' => '22:00:00',
            'is_available' => true,
        ]);
    }

    public function withHours(string $start, string $end): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $start,
            'end_time' => $end,
            'is_available' => true,
        ]);
    }

    // =========================================================================
    // Business Hours Pattern States
    // =========================================================================

    public function businessHours(): static
    {
        return $this->standardHours();
    }

    public function salonHours(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => '09:00:00',
            'end_time' => '19:00:00',
            'is_available' => true,
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

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a full week of availability for a provider.
     */
    public static function createWeekForProvider(
        Provider $provider,
        string $pattern = 'standard'
    ): \Illuminate\Database\Eloquent\Collection {
        $factory = new self;
        $patternConfig = $factory->businessHoursPatterns[$pattern] ?? $factory->businessHoursPatterns['standard'];

        $availabilities = collect();

        for ($day = 0; $day <= 6; $day++) {
            // Sunday (0) is usually closed
            $isAvailable = $day !== 0;

            // If weekdays only, Saturday is also closed
            if ($patternConfig['weekdays_only'] && $day === 6) {
                $isAvailable = false;
            }

            $availability = ProviderAvailability::factory()
                ->forProvider($provider)
                ->forDay($day)
                ->create([
                    'start_time' => $patternConfig['start'],
                    'end_time' => $patternConfig['end'],
                    'is_available' => $isAvailable,
                ]);

            $availabilities->push($availability);
        }

        return $availabilities;
    }
}
