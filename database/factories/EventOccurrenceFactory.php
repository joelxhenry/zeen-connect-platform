<?php

namespace Database\Factories;

use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventOccurrence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventOccurrence>
 */
class EventOccurrenceFactory extends Factory
{
    protected $model = EventOccurrence::class;

    public function definition(): array
    {
        $startDatetime = fake()->dateTimeBetween('+1 day', '+3 months');
        $durationMinutes = fake()->randomElement([60, 90, 120, 180, 240]);
        $endDatetime = (clone $startDatetime)->modify("+{$durationMinutes} minutes");
        $capacity = fake()->randomElement([10, 15, 20, 30, 50, null]);

        return [
            'uuid' => Str::uuid()->toString(),
            'event_id' => Event::factory(),
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'capacity_override' => null,
            'spots_remaining' => $capacity ?? PHP_INT_MAX,
            'status' => OccurrenceStatus::SCHEDULED,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OccurrenceStatus::SCHEDULED,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OccurrenceStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->randomElement([
                'Insufficient registrations',
                'Instructor unavailable',
                'Venue not available',
                'Weather conditions',
                'Emergency closure',
            ]),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $startDatetime = fake()->dateTimeBetween('-3 months', '-1 day');
            $durationMinutes = fake()->randomElement([60, 90, 120, 180]);
            $endDatetime = (clone $startDatetime)->modify("+{$durationMinutes} minutes");

            return [
                'status' => OccurrenceStatus::COMPLETED,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
            ];
        });
    }

    // =========================================================================
    // Time States
    // =========================================================================

    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $startDatetime = fake()->dateTimeBetween('+1 day', '+2 months');
            $durationMinutes = $attributes['event']?->duration_minutes ?? fake()->randomElement([60, 90, 120, 180]);
            $endDatetime = (clone $startDatetime)->modify("+{$durationMinutes} minutes");

            return [
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'status' => OccurrenceStatus::SCHEDULED,
            ];
        });
    }

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startDatetime = fake()->dateTimeBetween('-3 months', '-1 day');
            $durationMinutes = $attributes['event']?->duration_minutes ?? fake()->randomElement([60, 90, 120, 180]);
            $endDatetime = (clone $startDatetime)->modify("+{$durationMinutes} minutes");

            return [
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'status' => OccurrenceStatus::COMPLETED,
            ];
        });
    }

    public function today(): static
    {
        return $this->state(function (array $attributes) {
            $hour = fake()->numberBetween(10, 18);
            $startDatetime = now()->setHour($hour)->setMinute(0)->setSecond(0);
            $durationMinutes = $attributes['event']?->duration_minutes ?? fake()->randomElement([60, 90, 120]);
            $endDatetime = $startDatetime->copy()->addMinutes($durationMinutes);

            return [
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'status' => OccurrenceStatus::SCHEDULED,
            ];
        });
    }

    public function thisWeek(): static
    {
        return $this->state(function (array $attributes) {
            $startDatetime = fake()->dateTimeBetween('now', '+7 days');
            $durationMinutes = $attributes['event']?->duration_minutes ?? fake()->randomElement([60, 90, 120]);
            $endDatetime = (clone $startDatetime)->modify("+{$durationMinutes} minutes");

            return [
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'status' => OccurrenceStatus::SCHEDULED,
            ];
        });
    }

    public function at(\DateTimeInterface $datetime): static
    {
        return $this->state(function (array $attributes) use ($datetime) {
            $durationMinutes = $attributes['event']?->duration_minutes ?? 60;
            $startDatetime = \Carbon\Carbon::parse($datetime);
            $endDatetime = $startDatetime->copy()->addMinutes($durationMinutes);

            return [
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
            ];
        });
    }

    // =========================================================================
    // Capacity States
    // =========================================================================

    public function full(): static
    {
        return $this->state(fn (array $attributes) => [
            'spots_remaining' => 0,
        ]);
    }

    public function almostFull(): static
    {
        return $this->state(fn (array $attributes) => [
            'spots_remaining' => fake()->numberBetween(1, 3),
        ]);
    }

    public function hasAvailability(): static
    {
        return $this->state(fn (array $attributes) => [
            'spots_remaining' => fake()->numberBetween(5, 20),
        ]);
    }

    public function withCapacity(int $total, int $booked = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity_override' => $total,
            'spots_remaining' => $total - $booked,
        ]);
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forEvent(Event $event): static
    {
        return $this->state(function (array $attributes) use ($event) {
            $startDatetime = fake()->dateTimeBetween('+1 day', '+2 months');
            $endDatetime = (clone $startDatetime)->modify("+{$event->duration_minutes} minutes");

            return [
                'event_id' => $event->id,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'spots_remaining' => $event->capacity ?? PHP_INT_MAX,
            ];
        });
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create multiple occurrences for an event.
     */
    public static function createForEvent(Event $event, int $count = 5): \Illuminate\Database\Eloquent\Collection
    {
        $occurrences = collect();
        $startDate = now()->addDays(fake()->numberBetween(1, 7));

        for ($i = 0; $i < $count; $i++) {
            $occurrence = self::new()
                ->forEvent($event)
                ->create([
                    'start_datetime' => $startDate->copy()->addWeeks($i),
                    'end_datetime' => $startDate->copy()->addWeeks($i)->addMinutes($event->duration_minutes),
                ]);

            $occurrences->push($occurrence);
        }

        return $occurrences;
    }
}
