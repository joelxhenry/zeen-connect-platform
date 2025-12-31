<?php

namespace App\Domains\Event\Services;

use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventOccurrence;
use App\Domains\Event\Models\EventRecurrenceRule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurrenceService
{
    /**
     * Generate occurrences for a recurring event.
     *
     * @param  int  $months  Number of months ahead to generate (default 3)
     * @return Collection<EventOccurrence>
     */
    public function generateOccurrences(Event $event, int $months = 3): Collection
    {
        if (! $event->isRecurring() || ! $event->recurrenceRule) {
            return collect();
        }

        $rule = $event->recurrenceRule;
        $dates = $this->calculateDates($rule, $months);

        // Get existing occurrence dates to avoid duplicates
        $existingDates = $event->occurrences()
            ->pluck('start_datetime')
            ->map(fn ($dt) => $dt->format('Y-m-d H:i'))
            ->toArray();

        $occurrences = collect();

        foreach ($dates as $date) {
            $startDatetime = $this->buildStartDatetime($date, $rule);
            $dateKey = $startDatetime->format('Y-m-d H:i');

            // Skip if occurrence already exists
            if (in_array($dateKey, $existingDates)) {
                continue;
            }

            $occurrence = $this->createOccurrence($event, $startDatetime);
            $occurrences->push($occurrence);
        }

        return $occurrences;
    }

    /**
     * Calculate occurrence dates based on recurrence rule.
     *
     * @return Collection<Carbon>
     */
    protected function calculateDates(EventRecurrenceRule $rule, int $months): Collection
    {
        $dates = collect();
        $startDate = $rule->starts_at->copy();
        $endDate = $rule->ends_at ?? now()->addMonths($months);

        // Ensure we don't generate occurrences in the past
        if ($startDate->isPast()) {
            $startDate = now()->startOfDay();
        }

        // Ensure we have days of week configured
        $daysOfWeek = $rule->days_of_week ?? [];
        if (empty($daysOfWeek)) {
            return $dates;
        }

        $currentDate = $startDate->copy();
        $occurrenceCount = 0;
        $maxOccurrences = $rule->max_occurrences ?? PHP_INT_MAX;
        $interval = $rule->interval ?? 1;

        // Track which week we're in for interval calculation
        $weekStart = $currentDate->copy()->startOfWeek();

        while ($currentDate->lte($endDate) && $occurrenceCount < $maxOccurrences) {
            // Check if current day is in the selected days of week
            $dayOfWeek = $currentDate->dayOfWeek;

            if (in_array($dayOfWeek, $daysOfWeek)) {
                // Check if we're in a valid interval week
                $weeksSinceStart = $weekStart->diffInWeeks($currentDate->copy()->startOfWeek());

                if ($weeksSinceStart % $interval === 0) {
                    $dates->push($currentDate->copy());
                    $occurrenceCount++;
                }
            }

            $currentDate->addDay();

            // Safety limit to prevent infinite loops
            if ($dates->count() > 1000) {
                break;
            }
        }

        return $dates;
    }

    /**
     * Build the full start datetime from a date and the rule's time.
     */
    protected function buildStartDatetime(Carbon $date, EventRecurrenceRule $rule): Carbon
    {
        return $date->copy()->setTimeFromTimeString(
            $rule->time_of_day->format('H:i:s')
        );
    }

    /**
     * Create a single occurrence for an event.
     */
    protected function createOccurrence(Event $event, Carbon $startDatetime): EventOccurrence
    {
        $endDatetime = $startDatetime->copy()->addMinutes($event->duration_minutes);

        return EventOccurrence::create([
            'event_id' => $event->id,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'spots_remaining' => $event->capacity ?? PHP_INT_MAX,
            'status' => OccurrenceStatus::SCHEDULED,
        ]);
    }

    /**
     * Create a single one-time occurrence.
     */
    public function createOneTimeOccurrence(Event $event, Carbon $startDatetime): EventOccurrence
    {
        return $this->createOccurrence($event, $startDatetime);
    }

    /**
     * Delete future occurrences that have no bookings.
     */
    public function deleteFutureOccurrencesWithoutBookings(Event $event): int
    {
        return $event->occurrences()
            ->scheduled()
            ->upcoming()
            ->whereDoesntHave('bookings')
            ->delete();
    }

    /**
     * Cancel all future occurrences.
     */
    public function cancelFutureOccurrences(Event $event, ?string $reason = null): int
    {
        return $event->occurrences()
            ->scheduled()
            ->upcoming()
            ->update([
                'status' => OccurrenceStatus::CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);
    }

    /**
     * Mark past occurrences as completed.
     */
    public function markPastOccurrencesAsCompleted(Event $event): int
    {
        return $event->occurrences()
            ->scheduled()
            ->past()
            ->update([
                'status' => OccurrenceStatus::COMPLETED,
            ]);
    }
}
