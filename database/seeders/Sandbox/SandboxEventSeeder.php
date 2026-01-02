<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\EventType;
use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventOccurrence;
use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\SubscriptionTier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SandboxEventSeeder extends Seeder
{
    /**
     * Event templates for realistic data.
     */
    protected array $eventTemplates = [
        [
            'name' => 'Hair Styling Workshop',
            'description' => 'Learn professional hair styling techniques from our expert stylists.',
            'duration' => 120,
            'capacity' => 10,
            'price_range' => [50, 150],
        ],
        [
            'name' => 'Makeup Masterclass',
            'description' => 'Master the art of makeup application with hands-on training.',
            'duration' => 180,
            'capacity' => 8,
            'price_range' => [75, 200],
        ],
        [
            'name' => 'Nail Art Workshop',
            'description' => 'Create stunning nail art designs with our step-by-step guidance.',
            'duration' => 90,
            'capacity' => 6,
            'price_range' => [40, 100],
        ],
        [
            'name' => 'Skincare Basics',
            'description' => 'Discover the fundamentals of skincare for healthy, glowing skin.',
            'duration' => 60,
            'capacity' => 15,
            'price_range' => [30, 80],
        ],
        [
            'name' => 'Bridal Beauty Workshop',
            'description' => 'Learn bridal makeup and hair styling techniques for the big day.',
            'duration' => 240,
            'capacity' => 6,
            'price_range' => [100, 250],
        ],
        [
            'name' => 'VIP Pamper Night',
            'description' => 'An exclusive evening of pampering with complimentary refreshments.',
            'duration' => 180,
            'capacity' => 10,
            'price_range' => [100, 250],
        ],
        [
            'name' => 'Beauty Tips Webinar',
            'description' => 'Join us online for expert beauty tips and Q&A session.',
            'duration' => 60,
            'capacity' => 100,
            'price_range' => [0, 25],
            'virtual' => true,
        ],
        [
            'name' => 'Client Appreciation Day',
            'description' => 'A special day to thank our loyal clients with exclusive offers.',
            'duration' => 480,
            'capacity' => null,
            'price_range' => [0, 0],
        ],
    ];

    /**
     * Seed events for premium and enterprise providers.
     */
    public function run(): void
    {
        // Get premium and enterprise providers (events are a premium+ feature)
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

            // Determine event count based on tier
            $eventCount = match ($tier) {
                SubscriptionTier::PREMIUM => fake()->numberBetween(1, 3),
                SubscriptionTier::ENTERPRISE => fake()->numberBetween(2, 5),
                default => 0,
            };

            $this->createEventsForProvider($provider, $eventCount);
        }
    }

    protected function createEventsForProvider(Provider $provider, int $count): void
    {
        $usedTemplates = [];

        for ($i = 0; $i < $count; $i++) {
            // Pick a random template (avoid duplicates)
            $template = null;
            $attempts = 0;
            while ($template === null && $attempts < 10) {
                $candidate = fake()->randomElement($this->eventTemplates);
                if (!isset($usedTemplates[$candidate['name']])) {
                    $template = $candidate;
                    $usedTemplates[$candidate['name']] = true;
                }
                $attempts++;
            }

            if ($template === null) {
                continue;
            }

            $this->createEvent($provider, $template);
        }
    }

    protected function createEvent(Provider $provider, array $template): void
    {
        $isVirtual = $template['virtual'] ?? fake()->boolean(20);
        $locationType = $isVirtual ? EventLocationType::VIRTUAL : EventLocationType::IN_PERSON;

        // Determine status (80% published, 20% draft)
        $status = fake()->boolean(80) ? EventStatus::PUBLISHED : EventStatus::DRAFT;

        // Determine event type (70% one-time, 30% recurring)
        $eventType = fake()->boolean(70) ? EventType::ONE_TIME : EventType::RECURRING;

        // Calculate price
        $price = $template['price_range'][0] === $template['price_range'][1]
            ? $template['price_range'][0]
            : fake()->randomFloat(2, $template['price_range'][0], $template['price_range'][1]);

        $event = Event::create([
            'uuid' => Str::uuid()->toString(),
            'provider_id' => $provider->id,
            'name' => $template['name'],
            'description' => $template['description'],
            'event_type' => $eventType,
            'location_type' => $locationType,
            'location' => $isVirtual ? null : $provider->address ?? fake()->address(),
            'virtual_meeting_url' => $isVirtual ? 'https://zoom.us/j/' . fake()->numerify('##########') : null,
            'duration_minutes' => $template['duration'],
            'capacity' => $template['capacity'],
            'price' => $price,
            'settings' => ['use_provider_defaults' => true],
            'status' => $status,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // Create occurrences for published events
        if ($status === EventStatus::PUBLISHED) {
            $this->createOccurrencesForEvent($event);
        }
    }

    protected function createOccurrencesForEvent(Event $event): void
    {
        // Determine occurrence count
        $occurrenceCount = $event->event_type === EventType::RECURRING
            ? fake()->numberBetween(4, 8)
            : fake()->numberBetween(1, 3);

        $startDate = now()->addDays(fake()->numberBetween(7, 30));

        for ($i = 0; $i < $occurrenceCount; $i++) {
            // Calculate occurrence date
            $occurrenceDate = $event->event_type === EventType::RECURRING
                ? $startDate->copy()->addWeeks($i)
                : $startDate->copy()->addDays($i * fake()->numberBetween(7, 21));

            // Set a reasonable time
            $hour = fake()->numberBetween(10, 18);
            $occurrenceDate->setHour($hour)->setMinute(0)->setSecond(0);

            $endDatetime = $occurrenceDate->copy()->addMinutes($event->duration_minutes);

            // Determine spots remaining (some events might be partially booked)
            $capacity = $event->capacity ?? PHP_INT_MAX;
            $booked = $capacity === PHP_INT_MAX ? 0 : fake()->numberBetween(0, (int) ($capacity * 0.6));
            $spotsRemaining = $capacity === PHP_INT_MAX ? PHP_INT_MAX : max(0, $capacity - $booked);

            EventOccurrence::create([
                'uuid' => Str::uuid()->toString(),
                'event_id' => $event->id,
                'start_datetime' => $occurrenceDate,
                'end_datetime' => $endDatetime,
                'capacity_override' => null,
                'spots_remaining' => $spotsRemaining,
                'status' => OccurrenceStatus::SCHEDULED,
            ]);
        }
    }
}
