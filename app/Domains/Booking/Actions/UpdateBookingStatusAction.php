<?php

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Mail\BookingConfirmed;
use App\Mail\BookingStatusChanged;
use App\Mail\ReviewRequest;
use Illuminate\Support\Facades\Mail;

class UpdateBookingStatusAction
{
    public function execute(
        Booking $booking,
        BookingStatus $newStatus,
        ?string $reason = null,
        ?string $providerNotes = null
    ): Booking {
        // Validate status transition
        if (! $booking->status->canTransitionTo($newStatus)) {
            throw new \Exception("Cannot transition from {$booking->status->label()} to {$newStatus->label()}.");
        }

        $previousStatus = $booking->status->value;

        $data = [
            'status' => $newStatus,
        ];

        // Set timestamps based on new status
        match ($newStatus) {
            BookingStatus::CONFIRMED => $data['confirmed_at'] = now(),
            BookingStatus::COMPLETED => $data['completed_at'] = now(),
            BookingStatus::CANCELLED => [
                $data['cancelled_at'] = now(),
                $data['cancellation_reason'] = $reason,
            ],
            BookingStatus::NO_SHOW => $data['completed_at'] = now(),
            default => null,
        };

        if ($providerNotes) {
            $data['provider_notes'] = $providerNotes;
        }

        $booking->update($data);

        // Update provider stats
        if ($newStatus === BookingStatus::COMPLETED) {
            $booking->provider->increment('total_bookings');
            $booking->client->increment('total_bookings');
        }

        // Load relationships for email
        $booking->load(['client', 'provider', 'service']);

        // Send notification emails based on status change
        $this->sendStatusNotifications($booking, $previousStatus, $newStatus);

        return $booking->fresh();
    }

    protected function sendStatusNotifications(
        Booking $booking,
        string $previousStatus,
        BookingStatus $newStatus
    ): void {
        $client = $booking->client;
        $provider = $booking->provider;

        match ($newStatus) {
            BookingStatus::CONFIRMED => Mail::to($client->email)->send(new BookingConfirmed($booking)),

            BookingStatus::COMPLETED => [
                // Notify client that booking is completed
                Mail::to($client->email)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
                // Send review request after a short delay (queued)
                Mail::to($client->email)->later(now()->addHours(2), new ReviewRequest($booking)),
            ],

            BookingStatus::CANCELLED => [
                Mail::to($client->email)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
                Mail::to($provider->user->email)->send(new BookingStatusChanged($booking, $previousStatus, 'provider')),
            ],

            BookingStatus::NO_SHOW => [
                Mail::to($client->email)->send(new BookingStatusChanged($booking, $previousStatus, 'client')),
            ],

            default => null,
        };
    }
}
