<?php

namespace Database\Factories;

use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Models\Provider;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Positive review comments for high ratings (4-5 stars).
     */
    protected array $positiveComments = [
        "Absolutely amazing experience! {name} is incredibly talented and really listened to what I wanted.",
        "Best service I've ever had. Will definitely be coming back!",
        "Professional, friendly, and the results exceeded my expectations.",
        "Fantastic! The attention to detail was impressive.",
        "Love the results! {name} is a true professional.",
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

    public function definition(): array
    {
        // Weighted rating distribution: 45% 5-star, 30% 4-star, 15% 3-star, 7% 2-star, 3% 1-star
        $rating = $this->getWeightedRating();

        return [
            'uuid' => Str::uuid()->toString(),
            'booking_id' => Booking::factory(),
            'client_id' => User::factory(),
            'provider_id' => Provider::factory(),
            'service_id' => Service::factory(),
            'rating' => $rating,
            'comment' => $this->getCommentForRating($rating),
            'provider_response' => null,
            'provider_responded_at' => null,
            'is_visible' => true,
            'is_flagged' => false,
            'flag_reason' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
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

        $comment = fake()->randomElement($comments);

        // Replace placeholder with a random name
        return str_replace('{name}', fake()->firstName(), $comment);
    }

    // =========================================================================
    // Rating States
    // =========================================================================

    public function fiveStars(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 5,
            'comment' => $this->getCommentForRating(5),
        ]);
    }

    public function fourStars(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 4,
            'comment' => $this->getCommentForRating(4),
        ]);
    }

    public function threeStars(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 3,
            'comment' => $this->getCommentForRating(3),
        ]);
    }

    public function twoStars(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 2,
            'comment' => $this->getCommentForRating(2),
        ]);
    }

    public function oneStar(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 1,
            'comment' => $this->getCommentForRating(1),
        ]);
    }

    public function withRating(int $rating): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => max(1, min(5, $rating)),
            'comment' => $this->getCommentForRating($rating),
        ]);
    }

    // =========================================================================
    // Response States
    // =========================================================================

    public function withProviderResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_response' => fake()->randomElement($this->providerResponses),
            'provider_responded_at' => fake()->dateTimeBetween($attributes['created_at'] ?? '-1 month', 'now'),
        ]);
    }

    public function withoutProviderResponse(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_response' => null,
            'provider_responded_at' => null,
        ]);
    }

    // =========================================================================
    // Visibility States
    // =========================================================================

    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => true,
        ]);
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_visible' => false,
        ]);
    }

    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
            'flag_reason' => fake()->randomElement([
                'Inappropriate language',
                'Suspected fake review',
                'Contains personal information',
                'Spam or promotional content',
            ]),
        ]);
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forBooking(Booking $booking): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'provider_id' => $booking->provider_id,
            'service_id' => $booking->service_id,
        ]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => $service->id,
            'provider_id' => $service->provider_id,
        ]);
    }
}
