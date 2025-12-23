<?php

namespace App\Domains\Review\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Review\Models\Review;
use App\Models\User;
use InvalidArgumentException;

class CreateReviewAction
{
    /**
     * Create a review for a completed booking.
     */
    public function execute(Booking $booking, User $client, int $rating, ?string $comment = null): Review
    {
        // Validate booking ownership
        if ($booking->client_id !== $client->id) {
            throw new InvalidArgumentException('You can only review your own bookings.');
        }

        // Validate booking is completed
        if ($booking->status !== BookingStatus::COMPLETED) {
            throw new InvalidArgumentException('You can only review completed bookings.');
        }

        // Check if review already exists
        if ($booking->review()->exists()) {
            throw new InvalidArgumentException('This booking has already been reviewed.');
        }

        // Validate rating
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }

        return Review::create([
            'booking_id' => $booking->id,
            'client_id' => $client->id,
            'provider_id' => $booking->provider_id,
            'service_id' => $booking->service_id,
            'rating' => $rating,
            'comment' => $comment,
        ]);
    }
}
