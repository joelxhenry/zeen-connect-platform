<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventType;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Services\RecurrenceService;
use Carbon\Carbon;

class UpdateEventAction
{
    public function __construct(
        protected RecurrenceService $recurrenceService
    ) {}

    public function execute(Event $event, array $data): Event
    {
        $useDefaults = $data['use_provider_defaults'] ?? true;

        // Build settings JSON
        $settings = [
            'use_provider_defaults' => $useDefaults,
            'requires_approval' => $useDefaults ? null : ($data['requires_approval'] ?? null),
            'deposit_type' => $useDefaults ? null : ($data['deposit_type'] ?? null),
            'deposit_amount' => $useDefaults ? null : ($data['deposit_amount'] ?? null),
            'cancellation_policy' => $useDefaults ? null : ($data['cancellation_policy'] ?? null),
            'advance_booking_days' => $useDefaults ? null : ($data['advance_booking_days'] ?? null),
            'min_booking_notice_hours' => $useDefaults ? null : ($data['min_booking_notice_hours'] ?? null),
            'allow_waitlist' => $data['allow_waitlist'] ?? $event->getSetting('allow_waitlist', false),
            'max_spots_per_booking' => $data['max_spots_per_booking'] ?? $event->getSetting('max_spots_per_booking', 10),
        ];

        $event->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'event_type' => $data['event_type'] ?? $event->event_type,
            'location_type' => $data['location_type'] ?? $event->location_type,
            'location' => $data['location'] ?? null,
            'virtual_meeting_url' => $data['virtual_meeting_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? $event->duration_minutes,
            'capacity' => $data['capacity'] ?? $event->capacity,
            'price' => $data['price'] ?? $event->price,
            'settings' => $settings,
            'is_active' => $data['is_active'] ?? $event->is_active,
            'sort_order' => $data['sort_order'] ?? $event->sort_order,
        ]);

        // Sync categories if provided
        if (array_key_exists('category_ids', $data)) {
            $event->syncCategories($data['category_ids'] ?? []);
        }

        // Sync team members if provided
        if (array_key_exists('team_member_ids', $data)) {
            $event->teamMembers()->sync($data['team_member_ids'] ?? []);
        }

        // Update recurrence rule if provided and event is recurring
        if ($event->isRecurring() && isset($data['recurrence'])) {
            $this->updateRecurrenceRule($event, $data['recurrence']);
        }

        // Update one-time occurrence if provided
        if ($event->isOneTime() && isset($data['occurrence_datetime'])) {
            $this->updateOneTimeOccurrence($event, Carbon::parse($data['occurrence_datetime']));
        }

        return $event->fresh(['categories', 'teamMembers', 'recurrenceRule', 'occurrences']);
    }

    /**
     * Update the recurrence rule for a recurring event.
     */
    protected function updateRecurrenceRule(Event $event, array $recurrence): void
    {
        $event->recurrenceRule()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'frequency' => $recurrence['frequency'] ?? 'weekly',
                'interval' => $recurrence['interval'] ?? 1,
                'days_of_week' => $recurrence['days_of_week'] ?? [],
                'time_of_day' => $recurrence['time_of_day'],
                'starts_at' => $recurrence['starts_at'],
                'ends_at' => $recurrence['ends_at'] ?? null,
                'max_occurrences' => $recurrence['max_occurrences'] ?? null,
            ]
        );
    }

    /**
     * Update the one-time occurrence.
     */
    protected function updateOneTimeOccurrence(Event $event, Carbon $startDatetime): void
    {
        $occurrence = $event->occurrences()->first();

        if ($occurrence) {
            $occurrence->update([
                'start_datetime' => $startDatetime,
                'end_datetime' => $startDatetime->copy()->addMinutes($event->duration_minutes),
            ]);
        } else {
            $this->recurrenceService->createOneTimeOccurrence($event, $startDatetime);
        }
    }
}
