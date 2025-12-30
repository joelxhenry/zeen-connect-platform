<?php

namespace App\Domains\ProviderSite\Services;

use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Resources\BookingResource;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Services\FeeCalculator;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Resources\ServiceResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProviderSiteDataService
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected FeeCalculator $feeCalculator
    ) {}

    /**
     * Get all data needed for the home page.
     */
    public function getHomePageData(Provider $provider): array
    {
        $provider->load([
            'user:id,name,avatar,email',
            'availability' => fn ($q) => $q->where('is_available', true)->orderBy('day_of_week'),
            'services' => fn ($q) => $q->where('is_active', true)
                ->with(['category:id,name,icon,slug', 'media'])
                ->orderBy('sort_order'),
            'media',
            'videoEmbeds',
            'teamMembers' => fn ($q) => $q->active(),
        ]);

        return [
            'provider' => $this->formatProvider($provider),
            'servicesByCategory' => $this->getServicesByCategory($provider, 3),
            'availability' => $this->formatAvailability($provider),
            'reviews' => $this->getRecentReviews($provider, 5),
            'reviewStats' => $this->getReviewStats($provider),
            'teamMembers' => $this->formatTeamMembers($provider),
            'features' => $provider->site_features ?? [],
        ];
    }

    /**
     * Get all data needed for the services page.
     */
    public function getServicesPageData(Provider $provider): array
    {
        $provider->load([
            'services' => fn ($q) => $q->where('is_active', true)
                ->with(['category:id,name,icon,slug', 'media'])
                ->orderBy('sort_order'),
            'media',
            'videoEmbeds',
        ]);

        return [
            'provider' => $this->formatProvider($provider),
            'servicesByCategory' => $this->getServicesByCategory($provider),
        ];
    }

    /**
     * Get all data needed for the reviews page.
     */
    public function getReviewsPageData(Provider $provider, int $perPage = 10): array
    {
        $provider->load(['media', 'videoEmbeds']);

        return [
            'provider' => $this->formatProvider($provider),
            'reviews' => $this->getPaginatedReviews($provider, $perPage),
            'reviewStats' => $this->getReviewStats($provider),
        ];
    }

    /**
     * Get all data needed for the booking page.
     */
    public function getBookingPageData(
        Provider $provider,
        ?int $preselectedServiceId = null,
        ?object $user = null
    ): array {
        $provider->load([
            'user:id,name,avatar',
            'subscription',
            'services.category',
            'services.provider',
            'teamMembers' => fn ($q) => $q->active(),
        ]);

        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        $availableDates = $this->availabilityService->getAvailableDates($provider, $startDate, $endDate);

        $firstService = $provider->services->first();
        $tierInfo = $firstService
            ? $this->feeCalculator->calculateFees($provider, (float) $firstService->price, $firstService)->toArray()
            : null;

        return [
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
                'avatar' => $provider->user?->avatar,
                'tier' => Arr::get($tierInfo, 'tier', 'free'),
                'tier_label' => Arr::get($tierInfo, 'tier_label', 'Free'),
            ],
            'services' => $provider->services->map(
                fn ($service) => (new ServiceResource($service))->withCategory()->withFees()->resolve()
            ),
            'availableDates' => $availableDates,
            'preselectedService' => $preselectedServiceId,
            'isAuthenticated' => (bool) $user,
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
            ] : null,
            'teamMembers' => $provider->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'uuid' => $member->uuid,
                'name' => $member->display_name,
                'avatar' => $member->avatar,
            ]),
        ];
    }

    /**
     * Get confirmation page data.
     */
    public function getConfirmationPageData(Provider $provider, string $bookingUuid): array
    {
        $booking = Booking::where('uuid', $bookingUuid)
            ->where('provider_id', $provider->id)
            ->with([
                'client',
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,uuid,name,description,duration_minutes,price',
                'payment',
            ])
            ->firstOrFail();

        return [
            'booking' => (new BookingResource($booking))
                ->withProvider()
                ->withPayment()
                ->resolve(),
        ];
    }

    /**
     * Format provider data for frontend.
     */
    protected function formatProvider(Provider $provider): array
    {
        return [
            'id' => $provider->id,
            'uuid' => $provider->uuid,
            'slug' => $provider->slug,
            'domain' => $provider->domain,
            'business_name' => $provider->business_name,
            'tagline' => $provider->tagline,
            'bio' => $provider->bio,
            'avatar' => $provider->avatar_url ?? $provider->user?->avatar,
            'cover_image' => $provider->cover_photo_url,
            'website' => $provider->website,
            'social_links' => $provider->social_links,
            'address' => $provider->address,
            'rating_avg' => $provider->rating_avg,
            'rating_count' => $provider->rating_count,
            'rating_display' => $provider->rating_display,
            'total_bookings' => $provider->total_bookings,
            'is_featured' => $provider->is_featured,
            'verified_at' => $provider->verified_at,
            'services_count' => $provider->services->count(),
            'gallery' => $provider->relationLoaded('media')
                ? $provider->gallery->toArray()
                : [],
            'videos' => $provider->relationLoaded('videoEmbeds')
                ? $provider->videos->toArray()
                : [],
        ];
    }

    /**
     * Get services grouped by category.
     */
    protected function getServicesByCategory(Provider $provider, ?int $limitPerCategory = null): Collection
    {
        return $provider->services
            ->groupBy('category_id')
            ->map(function ($services, $categoryId) use ($limitPerCategory) {
                $category = $services->first()->category;

                $serviceCollection = $limitPerCategory
                    ? $services->take($limitPerCategory)
                    : $services;

                return [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'slug' => $category->slug,
                    ],
                    'services' => $serviceCollection->map(fn ($service) => [
                        'id' => $service->id,
                        'uuid' => $service->uuid,
                        'name' => $service->name,
                        'description' => $service->description,
                        'duration_minutes' => $service->duration_minutes,
                        'duration_display' => $service->duration_display,
                        'price' => (float) $service->price,
                        'price_display' => $service->price_display,
                        'display_image' => $service->display_image_url,
                    ]),
                ];
            })
            ->values();
    }

    /**
     * Format availability for display.
     */
    protected function formatAvailability(Provider $provider): Collection
    {
        return $provider->availability->map(fn ($slot) => [
            'day' => $slot->day_name,
            'day_of_week' => $slot->day_of_week,
            'start_time' => $slot->formatted_start_time,
            'end_time' => $slot->formatted_end_time,
        ]);
    }

    /**
     * Get recent reviews.
     */
    protected function getRecentReviews(Provider $provider, int $limit): Collection
    {
        return $provider->visibleReviews()
            ->with(['client:id,name,avatar', 'service:id,name'])
            ->recent()
            ->take($limit)
            ->get()
            ->map(fn ($review) => $this->formatReview($review));
    }

    /**
     * Get paginated reviews.
     */
    protected function getPaginatedReviews(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $provider->visibleReviews()
            ->with(['client:id,name,avatar', 'service:id,name'])
            ->recent()
            ->paginate($perPage)
            ->through(fn ($review) => $this->formatReview($review));
    }

    /**
     * Format a single review.
     */
    protected function formatReview(object $review): array
    {
        return [
            'id' => $review->id,
            'uuid' => $review->uuid,
            'client' => [
                'name' => $review->client->name,
                'avatar' => $review->client->avatar,
            ],
            'service_name' => $review->service->name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'provider_response' => $review->provider_response,
            'formatted_date' => $review->formatted_date,
            'time_ago' => $review->time_ago,
        ];
    }

    /**
     * Get review statistics for the provider.
     */
    protected function getReviewStats(Provider $provider): array
    {
        $ratingDistribution = $provider->visibleReviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $ratingDistribution[$i] ?? 0;
        }

        return [
            'total' => $provider->rating_count,
            'average' => $provider->rating_avg,
            'average_display' => $provider->rating_display,
            'distribution' => $distribution,
        ];
    }

    /**
     * Format team members for the home page.
     */
    protected function formatTeamMembers(Provider $provider): Collection
    {
        if (! $provider->relationLoaded('teamMembers')) {
            return collect([]);
        }

        return $provider->teamMembers->map(fn ($member) => [
            'id' => $member->id,
            'uuid' => $member->uuid,
            'name' => $member->display_name,
            'role' => $member->role,
            'avatar' => $member->avatar,
            'social_links' => $member->social_links,
        ]);
    }
}
