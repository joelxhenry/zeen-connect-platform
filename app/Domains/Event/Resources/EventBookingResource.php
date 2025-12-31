<?php

namespace App\Domains\Event\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventBookingResource extends JsonResource
{
    protected bool $includeOccurrence = false;
    protected bool $includeClient = false;

    /**
     * Include occurrence information.
     */
    public function withOccurrence(bool $include = true): self
    {
        $this->includeOccurrence = $include;

        return $this;
    }

    /**
     * Include client information.
     */
    public function withClient(bool $include = true): self
    {
        $this->includeClient = $include;

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
            'event_occurrence_id' => $this->event_occurrence_id,
            'booker_name' => $this->booker_name,
            'booker_email' => $this->booker_email,
            'booker_phone' => $this->booker_phone,
            'is_guest_booking' => $this->isGuestBooking(),
            'spots_booked' => (int) $this->spots_booked,
            'total_amount' => (float) $this->total_amount,
            'total_amount_display' => $this->total_amount_display,
            'deposit_amount' => $this->deposit_amount ? (float) $this->deposit_amount : null,
            'deposit_amount_display' => $this->deposit_amount_display,
            'deposit_paid' => (bool) $this->deposit_paid,
            'requires_deposit' => $this->requiresDeposit(),
            'amount_due' => $this->amount_due,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_pending' => $this->isPending(),
            'is_confirmed' => $this->isConfirmed(),
            'is_cancelled' => $this->isCancelled(),
            'has_attended' => $this->hasAttended(),
            'is_no_show' => $this->isNoShow(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'client_notes' => $this->client_notes,
            'provider_notes' => $this->provider_notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];

        if ($this->includeOccurrence) {
            $data['occurrence'] = $this->formatOccurrence();
        }

        if ($this->includeClient) {
            $data['client'] = $this->formatClient();
        }

        return $data;
    }

    /**
     * Format occurrence data.
     */
    protected function formatOccurrence(): ?array
    {
        if (! $this->relationLoaded('occurrence') || ! $this->occurrence) {
            return null;
        }

        return (new EventOccurrenceResource($this->occurrence))
            ->withEvent()
            ->resolve();
    }

    /**
     * Format client data.
     */
    protected function formatClient(): ?array
    {
        if ($this->isGuestBooking()) {
            return [
                'name' => $this->guest_name,
                'email' => $this->guest_email,
                'phone' => $this->guest_phone,
                'is_guest' => true,
            ];
        }

        if (! $this->relationLoaded('client') || ! $this->client) {
            return null;
        }

        return [
            'id' => $this->client->id,
            'name' => $this->client->name,
            'email' => $this->client->email,
            'phone' => $this->client->phone,
            'avatar' => $this->client->avatar,
            'is_guest' => false,
        ];
    }
}
