<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\EventType;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Services\RecurrenceService;
use App\Domains\Provider\Models\Provider;
use Carbon\Carbon;

class CreateEventAction
{
    public function __construct(
        protected RecurrenceService $recurrenceService
    ) {}

    public function execute(Provider $provider, array $data): Event
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
            'allow_waitlist' => $data['allow_waitlist'] ?? false,
            'max_spots_per_booking' => $data['max_spots_per_booking'] ?? 10,
        ];

        $event = $provider->events()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'event_type' => $data['event_type'] ?? EventType::ONE_TIME,
            'location_type' => $data['location_type'] ?? EventLocationType::IN_PERSON,
            'location' => $data['location'] ?? null,
            'virtual_meeting_url' => $data['virtual_meeting_url'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? 60,
            'capacity' => $data['capacity'] ?? null,
            'price' => $data['price'] ?? 0,
            'settings' => $settings,
            'status' => $data['status'] ?? EventStatus::DRAFT,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        // Sync categories if provided
        if (isset($data['category_ids'])) {
            $event->syncCategories($data['category_ids']);
        }

        // Sync team members if provided
        if (isset($data['team_member_ids'])) {
            $event->teamMembers()->sync($data['team_member_ids']);
        }

        // Handle recurrence rule for recurring events
        if ($event->isRecurring() && isset($data['recurrence'])) {
            $this->createRecurrenceRule($event, $data['recurrence']);
        }

        // Create occurrence for one-time events
        if ($event->isOneTime() && isset($data['occurrence_datetime'])) {
            $this->recurrenceService->createOneTimeOccurrence(
                $event,
                Carbon::parse($data['occurrence_datetime'])
            );
        }

        return $event->fresh(['categories', 'teamMembers', 'recurrenceRule', 'occurrences']);
    }

    /**
     * Create the recurrence rule for a recurring event.
     */
    protected function createRecurrenceRule(Event $event, array $recurrence): void
    {
        $event->recurrenceRule()->create([
            'frequency' => $recurrence['frequency'] ?? 'weekly',
            'interval' => $recurrence['interval'] ?? 1,
            'days_of_week' => $recurrence['days_of_week'] ?? [],
            'time_of_day' => $recurrence['time_of_day'],
            'starts_at' => $recurrence['starts_at'],
            'ends_at' => $recurrence['ends_at'] ?? null,
            'max_occurrences' => $recurrence['max_occurrences'] ?? null,
        ]);
    }
}
