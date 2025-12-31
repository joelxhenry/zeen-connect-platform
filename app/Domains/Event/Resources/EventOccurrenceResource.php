<?php

namespace App\Domains\Event\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventOccurrenceResource extends JsonResource
{
    protected bool $includeEvent = false;
    protected bool $includeBookings = false;

    /**
     * Include event information.
     */
    public function withEvent(bool $include = true): self
    {
        $this->includeEvent = $include;

        return $this;
    }

    /**
     * Include bookings.
     */
    public function withBookings(bool $include = true): self
    {
        $this->includeBookings = $include;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'event_id' => $this->event_id,
            'start_datetime' => $this->start_datetime->toIso8601String(),
            'end_datetime' => $this->end_datetime->toIso8601String(),
            'date_display' => $this->date_display,
            'time_display' => $this->time_display,
            'datetime_display' => $this->datetime_display,
            'capacity' => $this->getCapacity(),
            'capacity_override' => $this->capacity_override,
            'spots_remaining' => $this->getSpotsRemaining(),
            'has_availability' => $this->hasAvailability(),
            'is_full' => $this->isFull(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_scheduled' => $this->isScheduled(),
            'is_cancelled' => $this->isCancelled(),
            'is_completed' => $this->isCompleted(),
            'is_past' => $this->isPast(),
            'is_upcoming' => $this->isUpcoming(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
        ];

        if ($this->includeEvent) {
            $data['event'] = $this->formatEvent();
        }

        if ($this->includeBookings) {
            $data['bookings'] = $this->formatBookings();
            $data['confirmed_bookings_count'] = $this->getConfirmedBookingsCount();
        }

        return $data;
    }

    /**
     * Format event data.
     */
    protected function formatEvent(): ?array
    {
        if (! $this->relationLoaded('event') || ! $this->event) {
            return null;
        }

        return [
            'id' => $this->event->id,
            'uuid' => $this->event->uuid,
            'name' => $this->event->name,
            'duration_minutes' => $this->event->duration_minutes,
            'price' => (float) $this->event->price,
            'price_display' => $this->event->price_display,
        ];
    }

    /**
     * Format bookings data.
     */
    protected function formatBookings(): array
    {
        if (! $this->relationLoaded('bookings')) {
            return [];
        }

        return $this->bookings->map(fn ($booking) => (new EventBookingResource($booking))->resolve())->toArray();
    }
}
