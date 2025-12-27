<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Provider\Models\Provider;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSiteController extends Controller
{
    /**
     * Get the provider from the provider site middleware.
     */
    protected function getProvider(): Provider
    {
        return app('site.provider');
    }

    /**
     * Display the provider's homepage.
     */
    public function home(Request $request): Response
    {
        $provider = $this->getProvider();

        // Load additional relationships needed for homepage
        $provider->load([
            'user:id,name,avatar,email',
            'availability' => fn ($q) => $q->where('is_available', true)->orderBy('day_of_week'),
            'services' => fn ($q) => $q->where('is_active', true)->with(['category:id,name,icon,slug', 'media'])->orderBy('sort_order'),
            'media',
            'videoEmbeds',
        ]);

        // Group services by category
        $servicesByCategory = $provider->services
            ->groupBy('category_id')
            ->map(function ($services, $categoryId) {
                $category = $services->first()->category;

                return [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'slug' => $category->slug,
                    ],
                    'services' => $services->take(3)->map(fn ($service) => [
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

        // Format availability for display
        $availability = $provider->availability->map(fn ($slot) => [
            'day' => $slot->day_name,
            'day_of_week' => $slot->day_of_week,
            'start_time' => $slot->formatted_start_time,
            'end_time' => $slot->formatted_end_time,
        ]);

        // Get recent reviews (up to 5)
        $reviews = $provider->visibleReviews()
            ->with(['client:id,name,avatar', 'service:id,name'])
            ->recent()
            ->take(5)
            ->get()
            ->map(fn ($review) => [
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
            ]);

        return Inertia::render('ProviderSite/Home', [
            'provider' => $this->formatProvider($provider),
            'servicesByCategory' => $servicesByCategory,
            'availability' => $availability,
            'reviews' => $reviews,
            'reviewStats' => $this->getReviewStats($provider),
        ]);
    }

    /**
     * Display the provider's services page.
     */
    public function services(Request $request): Response
    {
        $provider = $this->getProvider();

        // Load full service details with categories and media
        $provider->load([
            'services' => fn ($q) => $q->where('is_active', true)->with(['category:id,name,icon,slug', 'media'])->orderBy('sort_order'),
            'media',
            'videoEmbeds',
        ]);

        // Group services by category
        $servicesByCategory = $provider->services
            ->groupBy('category_id')
            ->map(function ($services, $categoryId) {
                $category = $services->first()->category;

                return [
                    'category' => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'slug' => $category->slug,
                    ],
                    'services' => $services->map(fn ($service) => [
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

        return Inertia::render('ProviderSite/Services', [
            'provider' => $this->formatProvider($provider),
            'servicesByCategory' => $servicesByCategory,
        ]);
    }

    /**
     * Display the provider's reviews page.
     */
    public function reviews(Request $request): Response
    {
        $provider = $this->getProvider();

        // Load media for provider display
        $provider->load(['media', 'videoEmbeds']);

        // Paginated reviews
        $reviews = $provider->visibleReviews()
            ->with(['client:id,name,avatar', 'service:id,name'])
            ->recent()
            ->paginate(10)
            ->through(fn ($review) => [
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
            ]);

        return Inertia::render('ProviderSite/Reviews', [
            'provider' => $this->formatProvider($provider),
            'reviews' => $reviews,
            'reviewStats' => $this->getReviewStats($provider),
        ]);
    }

    /**
     * Format provider for frontend.
     */
    protected function formatProvider(Provider $provider): array
    {
        return [
            'id' => $provider->id,
            'uuid' => $provider->uuid,
            'slug' => $provider->slug,
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
}
