<?php

namespace App\Domains\ProviderSite\Services;

use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Resources\BookingResource;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
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
                ->with(['categories:id,name,slug', 'media'])
                ->orderBy('sort_order'),
            'media',
            'videoEmbeds',
            'teamMembers' => fn ($q) => $q->active(),
        ]);

        // Check if provider has any published events
        $hasEvents = Event::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->where('status', EventStatus::PUBLISHED)
            ->exists();

        return [
            'provider' => $this->formatProvider($provider),
            'servicesByCategory' => $this->getServicesByCategory($provider, 3),
            'availability' => $this->formatAvailability($provider),
            'reviews' => $this->getRecentReviews($provider, 5),
            'reviewStats' => $this->getReviewStats($provider),
            'teamMembers' => $this->formatTeamMembers($provider),
            'features' => $provider->site_features ?? [],
            'hasEvents' => $hasEvents,
        ];
    }

    /**
     * Get all data needed for the services page.
     */
    public function getServicesPageData(Provider $provider): array
    {
        $provider->load([
            'services' => fn ($q) => $q->where('is_active', true)
                ->with(['categories:id,name,slug', 'media'])
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
            'services.categories',
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
                fn ($service) => (new ServiceResource($service))->withCategories()->withFees()->resolve()
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
            ->groupBy(fn ($service) => $service->getPrimaryCategory()?->id ?? 0)
            ->map(function ($services) use ($limitPerCategory) {
                $primaryCategory = $services->first()->getPrimaryCategory();

                $serviceCollection = $limitPerCategory
                    ? $services->take($limitPerCategory)
                    : $services;

                return [
                    'category' => $primaryCategory ? [
                        'id' => $primaryCategory->id,
                        'name' => $primaryCategory->name,
                        'slug' => $primaryCategory->slug,
                    ] : null,
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
                        'categories' => $service->getCategoryNames(),
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

    /**
     * Get all data needed for the events listing page.
     */
    public function getEventsPageData(Provider $provider): array
    {
        $provider->load(['media', 'videoEmbeds']);

        // Get published, active events with upcoming occurrences
        $events = Event::where('provider_id', $provider->id)
            ->where('is_active', true)
            ->where('status', EventStatus::PUBLISHED)
            ->with([
                'categories:id,name,slug',
                'media',
                'occurrences' => fn ($q) => $q
                    ->where('status', OccurrenceStatus::SCHEDULED)
                    ->where('start_datetime', '>', now())
                    ->orderBy('start_datetime')
                    ->limit(3),
            ])
            ->orderBy('sort_order')
            ->get();

        return [
            'provider' => $this->formatProvider($provider),
            'eventsByCategory' => $this->getEventsByCategory($events),
            'hasEvents' => $events->isNotEmpty(),
        ];
    }

    /**
     * Get data for a single event detail page.
     */
    public function getEventDetailData(Provider $provider, Event $event): array
    {
        $provider->load(['media', 'videoEmbeds']);

        $event->load([
            'categories:id,name,slug',
            'media',
            'teamMembers' => fn ($q) => $q->active(),
            'occurrences' => fn ($q) => $q
                ->where('status', OccurrenceStatus::SCHEDULED)
                ->where('start_datetime', '>', now())
                ->orderBy('start_datetime')
                ->limit(10),
        ]);

        return [
            'provider' => $this->formatProvider($provider),
            'event' => $this->formatEvent($event),
            'occurrences' => $this->formatEventOccurrences($event),
        ];
    }

    /**
     * Get events grouped by category.
     */
    protected function getEventsByCategory(Collection $events): Collection
    {
        return $events
            ->groupBy(fn ($event) => $event->categories->first()?->id ?? 0)
            ->map(function ($groupedEvents) {
                $category = $groupedEvents->first()->categories->first();

                return [
                    'category' => $category ? [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                    ] : null,
                    'events' => $groupedEvents->map(fn ($event) => $this->formatEventSummary($event)),
                ];
            })
            ->values();
    }

    /**
     * Format an event for listing display.
     */
    protected function formatEventSummary(Event $event): array
    {
        $nextOccurrence = $event->occurrences->first();

        return [
            'id' => $event->id,
            'uuid' => $event->uuid,
            'slug' => $event->slug,
            'name' => $event->name,
            'description' => $event->description,
            'price' => (float) $event->price,
            'price_display' => $event->price_display,
            'duration_minutes' => $event->duration_minutes,
            'duration_display' => $event->duration_display,
            'capacity' => $event->capacity,
            'event_type' => $event->event_type->value,
            'location_type' => $event->location_type->value,
            'location' => $event->location,
            'display_image' => $event->display_image_url,
            'categories' => $event->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'next_occurrence' => $nextOccurrence ? [
                'id' => $nextOccurrence->id,
                'start_datetime' => $nextOccurrence->start_datetime->toIso8601String(),
                'end_datetime' => $nextOccurrence->end_datetime->toIso8601String(),
                'formatted_date' => $nextOccurrence->start_datetime->format('M j, Y'),
                'formatted_time' => $nextOccurrence->start_datetime->format('g:i A'),
                'spots_remaining' => $nextOccurrence->spots_remaining,
            ] : null,
            'occurrences_count' => $event->occurrences->count(),
        ];
    }

    /**
     * Format a full event for detail page.
     */
    protected function formatEvent(Event $event): array
    {
        return [
            'id' => $event->id,
            'uuid' => $event->uuid,
            'slug' => $event->slug,
            'name' => $event->name,
            'description' => $event->description,
            'price' => (float) $event->price,
            'price_display' => $event->price_display,
            'duration_minutes' => $event->duration_minutes,
            'duration_display' => $event->duration_display,
            'capacity' => $event->capacity,
            'event_type' => $event->event_type->value,
            'location_type' => $event->location_type->value,
            'location' => $event->location,
            'virtual_meeting_url' => $event->virtual_meeting_url,
            'display_image' => $event->display_image_url,
            'gallery' => $event->relationLoaded('media')
                ? $event->getMedia('gallery')->map(fn ($media) => [
                    'url' => $media->getUrl(),
                    'thumbnail' => $media->getUrl('thumbnail'),
                ])->toArray()
                : [],
            'categories' => $event->categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ]),
            'team_members' => $event->relationLoaded('teamMembers')
                ? $event->teamMembers->map(fn ($member) => [
                    'id' => $member->id,
                    'uuid' => $member->uuid,
                    'name' => $member->display_name,
                    'avatar' => $member->avatar,
                ])
                : [],
        ];
    }

    /**
     * Format event occurrences for booking selection.
     */
    protected function formatEventOccurrences(Event $event): Collection
    {
        return $event->occurrences->map(fn ($occurrence) => [
            'id' => $occurrence->id,
            'uuid' => $occurrence->uuid,
            'start_datetime' => $occurrence->start_datetime->toIso8601String(),
            'end_datetime' => $occurrence->end_datetime->toIso8601String(),
            'formatted_date' => $occurrence->start_datetime->format('l, M j, Y'),
            'formatted_time' => $occurrence->start_datetime->format('g:i A') . ' - ' . $occurrence->end_datetime->format('g:i A'),
            'capacity' => $occurrence->capacity_override ?? $event->capacity,
            'spots_remaining' => $occurrence->spots_remaining,
            'is_sold_out' => $occurrence->spots_remaining <= 0,
            'status' => $occurrence->status->value,
        ]);
    }

    /**
     * Get all data needed for the event booking page.
     */
    public function getEventBookingPageData(
        Provider $provider,
        Event $event,
        ?int $preselectedOccurrenceId = null,
        ?object $user = null
    ): array {
        $provider->load([
            'user:id,name,avatar',
            'subscription',
        ]);

        $event->load([
            'categories:id,name,slug',
            'media',
            'teamMembers' => fn ($q) => $q->active(),
            'occurrences' => fn ($q) => $q
                ->where('status', OccurrenceStatus::SCHEDULED)
                ->where('start_datetime', '>', now())
                ->where('spots_remaining', '>', 0)
                ->orderBy('start_datetime')
                ->limit(20),
        ]);

        return [
            'bookingType' => 'event',
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
                'domain' => $provider->domain,
                'avatar' => $provider->user?->avatar,
            ],
            'event' => $this->formatEvent($event),
            'occurrences' => $this->formatEventOccurrences($event),
            'preselectedOccurrence' => $preselectedOccurrenceId,
            'isAuthenticated' => (bool) $user,
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? null,
            ] : null,
        ];
    }

    /**
     * Get data for the My Bookings page.
     */
    public function getMyBookingsPageData(Provider $provider, object $user): array
    {
        // Get service bookings
        $serviceBookings = Booking::where('provider_id', $provider->id)
            ->where('client_id', $user->id)
            ->with([
                'service:id,uuid,name,duration_minutes,price',
                'provider:id,business_name,slug',
                'teamMember:id,uuid,display_name,avatar',
            ])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(fn ($booking) => [
                'type' => 'service',
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'date' => $booking->date->format('Y-m-d'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'service' => [
                    'id' => $booking->service->id,
                    'uuid' => $booking->service->uuid,
                    'name' => $booking->service->name,
                    'duration_minutes' => $booking->service->duration_minutes,
                    'price' => (float) $booking->service->price,
                ],
                'team_member' => $booking->teamMember ? [
                    'id' => $booking->teamMember->id,
                    'name' => $booking->teamMember->display_name,
                    'avatar' => $booking->teamMember->avatar,
                ] : null,
                'total_amount' => (float) $booking->total_amount,
                'total_amount_display' => '$' . number_format($booking->total_amount, 2),
                'deposit_paid' => $booking->deposit_paid,
                'requires_deposit' => $booking->requiresDeposit(),
                'can_cancel' => $booking->canBeCancelled(),
                'created_at' => $booking->created_at->toIso8601String(),
            ]);

        // Get event bookings
        $eventBookings = \App\Domains\Event\Models\EventBooking::whereHas('occurrence.event', function ($q) use ($provider) {
            $q->where('provider_id', $provider->id);
        })
            ->where('client_id', $user->id)
            ->with([
                'occurrence.event:id,uuid,name,slug,duration_minutes,price,location_type,location',
                'occurrence:id,event_id,start_datetime,end_datetime,status',
            ])
            ->orderByDesc(function ($query) {
                $query->select('start_datetime')
                    ->from('event_occurrences')
                    ->whereColumn('event_occurrences.id', 'event_bookings.event_occurrence_id');
            })
            ->get()
            ->map(fn ($booking) => [
                'type' => 'event',
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'date' => $booking->occurrence->start_datetime->format('Y-m-d'),
                'formatted_date' => $booking->occurrence->start_datetime->format('l, M j, Y'),
                'formatted_time' => $booking->occurrence->start_datetime->format('g:i A') . ' - ' . $booking->occurrence->end_datetime->format('g:i A'),
                'event' => [
                    'id' => $booking->occurrence->event->id,
                    'uuid' => $booking->occurrence->event->uuid,
                    'name' => $booking->occurrence->event->name,
                    'slug' => $booking->occurrence->event->slug,
                    'duration_minutes' => $booking->occurrence->event->duration_minutes,
                    'price' => (float) $booking->occurrence->event->price,
                    'location_type' => $booking->occurrence->event->location_type->value,
                    'location' => $booking->occurrence->event->location,
                ],
                'spots_booked' => $booking->spots_booked,
                'total_amount' => (float) $booking->total_amount,
                'total_amount_display' => '$' . number_format($booking->total_amount, 2),
                'deposit_paid' => $booking->deposit_paid,
                'requires_deposit' => $booking->requiresDeposit(),
                'can_cancel' => !$booking->isCancelled() && $booking->occurrence->isUpcoming(),
                'created_at' => $booking->created_at->toIso8601String(),
            ]);

        // Merge and sort all bookings by date descending
        $allBookings = $serviceBookings->merge($eventBookings)
            ->sortByDesc('date')
            ->values();

        // Separate into upcoming and past
        $today = now()->format('Y-m-d');
        $upcomingBookings = $allBookings->filter(fn ($b) => $b['date'] >= $today)->values();
        $pastBookings = $allBookings->filter(fn ($b) => $b['date'] < $today)->values();

        return [
            'provider' => $this->formatProvider($provider),
            'upcomingBookings' => $upcomingBookings,
            'pastBookings' => $pastBookings,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    /**
     * Get confirmation page data for event bookings.
     */
    public function getEventConfirmationPageData(Provider $provider, string $bookingUuid): array
    {
        $booking = \App\Domains\Event\Models\EventBooking::where('uuid', $bookingUuid)
            ->with([
                'client:id,name,email,phone,avatar',
                'occurrence.event.provider:id,business_name,slug,address',
                'occurrence.event.provider.user:id,name,avatar,email',
            ])
            ->firstOrFail();

        // Verify booking belongs to this provider
        if ($booking->occurrence->event->provider_id !== $provider->id) {
            abort(404);
        }

        return [
            'bookingType' => 'event',
            'booking' => [
                'id' => $booking->id,
                'uuid' => $booking->uuid,
                'status' => $booking->status->value,
                'status_label' => $booking->status->label(),
                'spots_booked' => $booking->spots_booked,
                'total_amount' => (float) $booking->total_amount,
                'total_amount_display' => $booking->total_amount_display,
                'deposit_amount' => $booking->deposit_amount ? (float) $booking->deposit_amount : null,
                'deposit_amount_display' => $booking->deposit_amount_display,
                'deposit_paid' => $booking->deposit_paid,
                'requires_deposit' => $booking->requiresDeposit(),
                'client_notes' => $booking->client_notes,
                'confirmed_at' => $booking->confirmed_at?->toIso8601String(),
                'booker' => [
                    'name' => $booking->booker_name,
                    'email' => $booking->booker_email,
                    'phone' => $booking->booker_phone,
                ],
                'event' => [
                    'id' => $booking->occurrence->event->id,
                    'uuid' => $booking->occurrence->event->uuid,
                    'name' => $booking->occurrence->event->name,
                    'slug' => $booking->occurrence->event->slug,
                    'description' => $booking->occurrence->event->description,
                    'price' => (float) $booking->occurrence->event->price,
                    'price_display' => $booking->occurrence->event->price_display,
                    'duration_display' => $booking->occurrence->event->duration_display,
                    'location_type' => $booking->occurrence->event->location_type->value,
                    'location' => $booking->occurrence->event->location,
                ],
                'occurrence' => [
                    'id' => $booking->occurrence->id,
                    'uuid' => $booking->occurrence->uuid,
                    'formatted_date' => $booking->occurrence->start_datetime->format('l, M j, Y'),
                    'formatted_time' => $booking->occurrence->start_datetime->format('g:i A') . ' - ' . $booking->occurrence->end_datetime->format('g:i A'),
                    'start_datetime' => $booking->occurrence->start_datetime->toIso8601String(),
                    'end_datetime' => $booking->occurrence->end_datetime->toIso8601String(),
                ],
                'provider' => [
                    'id' => $booking->occurrence->event->provider->id,
                    'business_name' => $booking->occurrence->event->provider->business_name,
                    'slug' => $booking->occurrence->event->provider->slug,
                    'address' => $booking->occurrence->event->provider->address,
                    'avatar' => $booking->occurrence->event->provider->user?->avatar,
                ],
            ],
        ];
    }
}
