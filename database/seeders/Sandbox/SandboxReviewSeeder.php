<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Review\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SandboxReviewSeeder extends Seeder
{
    /**
     * Positive review comments for high ratings (4-5 stars).
     */
    protected array $positiveComments = [
        "Absolutely amazing experience! The team is incredibly talented and really listened to what I wanted.",
        "Best service I've ever had. Will definitely be coming back!",
        "Professional, friendly, and the results exceeded my expectations.",
        "Fantastic! The attention to detail was impressive.",
        "Love the results! They are true professionals.",
        "Great experience from start to finish. Highly recommend!",
        "The staff was so welcoming and the service was top-notch.",
        "Couldn't be happier with the results. Worth every penny!",
        "Amazing atmosphere and even better service!",
        "They really know what they're doing. I felt pampered the entire time.",
    ];

    /**
     * Neutral review comments for medium ratings (3 stars).
     */
    protected array $neutralComments = [
        "Good service overall, but nothing exceptional.",
        "Decent experience. The results were okay.",
        "Average service, would consider returning.",
        "Service was fine, but wait time was longer than expected.",
        "Good but room for improvement in some areas.",
    ];

    /**
     * Negative review comments for low ratings (1-2 stars).
     */
    protected array $negativeComments = [
        "Not satisfied with the results. Expected better for the price.",
        "Service was okay but communication could be improved.",
        "Had to wait longer than expected. Service was rushed.",
        "Disappointed with the outcome. Won't be returning.",
        "Below average experience. Expected more based on reviews.",
    ];

    /**
     * Provider response templates.
     */
    protected array $providerResponses = [
        "Thank you so much for your kind words! We're thrilled you had a great experience and look forward to seeing you again!",
        "We appreciate your feedback! Your satisfaction is our top priority.",
        "Thank you for taking the time to leave a review. We're so glad you enjoyed your visit!",
        "We're sorry to hear your experience wasn't perfect. We'd love to make it right - please reach out to us directly.",
        "Thank you for your honest feedback. We're always working to improve our services.",
    ];

    /**
     * Seed reviews for completed bookings.
     */
    public function run(): void
    {
        // Get completed bookings that don't have reviews yet
        // 30% of completed bookings get reviews
        $completedBookings = Booking::with(['client', 'provider', 'service'])
            ->where('status', BookingStatus::COMPLETED)
            ->whereDoesntHave('review')
            ->whereNotNull('client_id') // Only registered users can review
            ->inRandomOrder()
            ->get();

        $reviewCount = (int) ($completedBookings->count() * 0.30);
        $bookingsToReview = $completedBookings->take($reviewCount);

        foreach ($bookingsToReview as $booking) {
            $this->createReviewForBooking($booking);
        }
    }

    protected function createReviewForBooking(Booking $booking): void
    {
        // Weighted rating distribution: 45% 5-star, 30% 4-star, 15% 3-star, 7% 2-star, 3% 1-star
        $rating = $this->getWeightedRating();

        // Get appropriate comment based on rating
        $comment = $this->getCommentForRating($rating);

        // 40% have provider responses
        $hasResponse = fake()->boolean(40);

        $review = Review::create([
            'uuid' => Str::uuid()->toString(),
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'provider_id' => $booking->provider_id,
            'service_id' => $booking->service_id,
            'rating' => $rating,
            'comment' => $comment,
            'provider_response' => $hasResponse ? fake()->randomElement($this->providerResponses) : null,
            'provider_responded_at' => $hasResponse ? fake()->dateTimeBetween($booking->completed_at ?? '-1 month', 'now') : null,
            'is_visible' => true,
            'is_flagged' => false,
            'created_at' => fake()->dateTimeBetween($booking->completed_at ?? '-6 months', 'now'),
        ]);

        // Update provider rating stats
        $booking->provider->updateRatingStats();
    }

    protected function getWeightedRating(): int
    {
        $rand = fake()->numberBetween(1, 100);

        if ($rand <= 45) {
            return 5;
        }
        if ($rand <= 75) {
            return 4;
        }
        if ($rand <= 90) {
            return 3;
        }
        if ($rand <= 97) {
            return 2;
        }

        return 1;
    }

    protected function getCommentForRating(int $rating): string
    {
        $comments = match (true) {
            $rating >= 4 => $this->positiveComments,
            $rating === 3 => $this->neutralComments,
            default => $this->negativeComments,
        };

        return fake()->randomElement($comments);
    }
}
