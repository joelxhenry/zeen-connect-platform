<?php

namespace Database\Factories;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\EventType;
use App\Domains\Event\Models\Event;
use App\Domains\Provider\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    /**
     * Event name templates for realistic data.
     */
    protected array $eventTemplates = [
        'workshop' => [
            ['name' => 'Hair Styling Workshop', 'duration' => 120, 'capacity' => 10, 'price_range' => [50, 150]],
            ['name' => 'Makeup Masterclass', 'duration' => 180, 'capacity' => 8, 'price_range' => [75, 200]],
            ['name' => 'Nail Art Workshop', 'duration' => 90, 'capacity' => 6, 'price_range' => [40, 100]],
            ['name' => 'Skincare Basics', 'duration' => 60, 'capacity' => 15, 'price_range' => [30, 80]],
        ],
        'class' => [
            ['name' => 'Yoga Class', 'duration' => 60, 'capacity' => 20, 'price_range' => [15, 30]],
            ['name' => 'Meditation Session', 'duration' => 45, 'capacity' => 15, 'price_range' => [10, 25]],
            ['name' => 'Pilates Class', 'duration' => 60, 'capacity' => 12, 'price_range' => [20, 40]],
            ['name' => 'Fitness Bootcamp', 'duration' => 45, 'capacity' => 20, 'price_range' => [15, 35]],
        ],
        'special' => [
            ['name' => 'Grand Opening Event', 'duration' => 240, 'capacity' => 50, 'price_range' => [0, 0]],
            ['name' => 'VIP Pamper Night', 'duration' => 180, 'capacity' => 10, 'price_range' => [100, 250]],
            ['name' => 'Client Appreciation Day', 'duration' => 480, 'capacity' => null, 'price_range' => [0, 0]],
            ['name' => 'Holiday Glam Event', 'duration' => 120, 'capacity' => 15, 'price_range' => [50, 120]],
        ],
        'webinar' => [
            ['name' => 'Beauty Tips Webinar', 'duration' => 60, 'capacity' => 100, 'price_range' => [0, 25]],
            ['name' => 'Product Launch Livestream', 'duration' => 45, 'capacity' => null, 'price_range' => [0, 0]],
            ['name' => 'Q&A with the Team', 'duration' => 60, 'capacity' => 50, 'price_range' => [0, 0]],
        ],
    ];

    protected array $descriptions = [
        'Join us for an exciting {event} where you\'ll learn new skills and connect with fellow beauty enthusiasts.',
        'Don\'t miss our exclusive {event}! Limited spots available.',
        'Experience a transformative {event} led by our expert team.',
        'This {event} is perfect for beginners and experienced participants alike.',
        'Register now for our highly anticipated {event}!',
    ];

    public function definition(): array
    {
        $category = fake()->randomElement(array_keys($this->eventTemplates));
        $template = fake()->randomElement($this->eventTemplates[$category]);
        $locationType = fake()->randomElement(EventLocationType::cases());

        return [
            'uuid' => Str::uuid()->toString(),
            'provider_id' => Provider::factory(),
            'name' => $template['name'],
            'description' => str_replace('{event}', strtolower($template['name']), fake()->randomElement($this->descriptions)),
            'event_type' => EventType::ONE_TIME,
            'location_type' => $locationType,
            'location' => $locationType === EventLocationType::IN_PERSON ? fake()->address() : null,
            'virtual_meeting_url' => $locationType === EventLocationType::VIRTUAL ? 'https://zoom.us/j/' . fake()->numerify('##########') : null,
            'duration_minutes' => $template['duration'],
            'capacity' => $template['capacity'],
            'price' => $template['price_range'][0] === $template['price_range'][1]
                ? $template['price_range'][0]
                : fake()->randomFloat(2, $template['price_range'][0], $template['price_range'][1]),
            'settings' => [
                'use_provider_defaults' => true,
            ],
            'status' => EventStatus::PUBLISHED,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Event Type States
    // =========================================================================

    public function oneTime(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::ONE_TIME,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => EventType::RECURRING,
        ]);
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::PUBLISHED,
            'is_active' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::DRAFT,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => EventStatus::CANCELLED,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    // =========================================================================
    // Location States
    // =========================================================================

    public function inPerson(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => EventLocationType::IN_PERSON,
            'location' => fake()->address(),
            'virtual_meeting_url' => null,
        ]);
    }

    public function virtual(): static
    {
        return $this->state(fn (array $attributes) => [
            'location_type' => EventLocationType::VIRTUAL,
            'location' => null,
            'virtual_meeting_url' => 'https://zoom.us/j/' . fake()->numerify('##########'),
        ]);
    }

    // =========================================================================
    // Category States
    // =========================================================================

    public function workshop(): static
    {
        return $this->fromCategory('workshop');
    }

    public function class(): static
    {
        return $this->fromCategory('class');
    }

    public function special(): static
    {
        return $this->fromCategory('special');
    }

    public function webinar(): static
    {
        return $this->fromCategory('webinar')->virtual();
    }

    public function fromCategory(string $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            $templates = $this->eventTemplates[$category] ?? $this->eventTemplates['workshop'];
            $template = fake()->randomElement($templates);

            return [
                'name' => $template['name'],
                'description' => str_replace('{event}', strtolower($template['name']), fake()->randomElement($this->descriptions)),
                'duration_minutes' => $template['duration'],
                'capacity' => $template['capacity'],
                'price' => $template['price_range'][0] === $template['price_range'][1]
                    ? $template['price_range'][0]
                    : fake()->randomFloat(2, $template['price_range'][0], $template['price_range'][1]),
            ];
        });
    }

    // =========================================================================
    // Pricing States
    // =========================================================================

    public function free(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => 0,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => fake()->randomFloat(2, 20, 200),
        ]);
    }

    public function withPrice(float $price): static
    {
        return $this->state(fn (array $attributes) => [
            'price' => $price,
        ]);
    }

    // =========================================================================
    // Capacity States
    // =========================================================================

    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => null,
        ]);
    }

    public function limited(int $capacity): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => $capacity,
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
