<?php

namespace App\Http\Controllers;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderListingController extends Controller
{
    /**
     * Display the provider listing/explore page.
     */
    public function index(Request $request): Response
    {
        $query = Provider::query()
            ->active()
            ->with([
                'user:id,name,avatar',
                'services' => fn ($q) => $q->where('is_active', true)->with('categories')->limit(3),
            ])
            ->withCount(['services' => fn ($q) => $q->where('is_active', true)]);

        // Filter by category (providers who have services in this category)
        if ($request->filled('category')) {
            $query->whereHas('services.categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category)
                    ->orWhere('categories.slug', $request->category);
            });
        }

        // Search by business name or tagline
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('tagline', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $request->get('sort', 'featured');
        switch ($sortBy) {
            case 'rating':
                $query->orderByDesc('rating_avg')->orderByDesc('rating_count');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            case 'name':
                $query->orderBy('business_name');
                break;
            case 'featured':
            default:
                $query->orderByDesc('is_featured')->orderByDesc('rating_avg');
                break;
        }

        $providers = $query->paginate(12)->withQueryString();

        // Get user's favorite provider IDs
        $user = $request->user();
        $favoriteIds = $user ? $user->favoriteProviders()->pluck('providers.id')->toArray() : [];

        // Transform providers for frontend
        $providers->getCollection()->transform(function ($provider) use ($favoriteIds) {
            return [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'slug' => $provider->slug,
                'business_name' => $provider->business_name,
                'tagline' => $provider->tagline,
                'avatar' => $provider->user?->avatar,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
                'services_count' => $provider->services_count,
                'is_featured' => $provider->is_featured,
                'is_favorited' => in_array($provider->id, $favoriteIds),
                'categories' => $provider->services
                    ->flatMap(fn ($service) => $service->categories)
                    ->unique('id')
                    ->values()
                    ->map(fn ($cat) => [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ]),
                'preview_services' => $provider->services->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service->price,
                    'duration_display' => $service->duration_display,
                ]),
            ];
        });

        // Get filter options - aggregate unique category names from active providers
        $categories = Category::query()
            ->whereHas('provider', fn ($q) => $q->active())
            ->where('is_active', true)
            ->select('id', 'uuid', 'name', 'slug')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values();

        return Inertia::render('Explore/Index', [
            'providers' => $providers,
            'categories' => $categories,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'sort' => $sortBy,
            ],
        ]);
    }

    /**
     * Display a public provider profile.
     */
    public function show(Request $request, string $slug): Response
    {
        $provider = Provider::query()
            ->where('slug', $slug)
            ->active()
            ->with([
                'user:id,name,avatar,email',
                'services' => fn ($q) => $q->where('is_active', true)->with('categories')->orderBy('sort_order'),
                'availability' => fn ($q) => $q->where('is_available', true)->orderBy('day_of_week'),
            ])
            ->firstOrFail();

        // Group services by primary category
        $servicesByCategory = $provider->services
            ->groupBy(fn ($service) => $service->getPrimaryCategory()?->id ?? 0)
            ->map(function ($services) {
                $primaryCategory = $services->first()->getPrimaryCategory();

                return [
                    'category' => $primaryCategory ? [
                        'id' => $primaryCategory->id,
                        'name' => $primaryCategory->name,
                        'slug' => $primaryCategory->slug,
                    ] : null,
                    'services' => $services->map(fn ($service) => [
                        'id' => $service->id,
                        'uuid' => $service->uuid,
                        'name' => $service->name,
                        'description' => $service->description,
                        'duration_minutes' => $service->duration_minutes,
                        'duration_display' => $service->duration_display,
                        'price' => $service->price,
                        'price_display' => $service->price_display,
                        'categories' => $service->getCategoryNames(),
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

        // Get recent reviews
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

        // Get rating distribution
        $ratingDistribution = $provider->visibleReviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $ratingDistribution[$i] ?? 0;
        }

        // Check if current user has favorited this provider
        $user = $request->user();
        $isFavorited = $user ? $user->hasFavorited($provider) : false;

        return Inertia::render('Explore/Provider', [
            'provider' => [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'slug' => $provider->slug,
                'business_name' => $provider->business_name,
                'tagline' => $provider->tagline,
                'bio' => $provider->bio,
                'avatar' => $provider->user?->avatar,
                'website' => $provider->website,
                'social_links' => $provider->social_links,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
                'total_bookings' => $provider->total_bookings,
                'is_featured' => $provider->is_featured,
                'is_favorited' => $isFavorited,
                'verified_at' => $provider->verified_at,
            ],
            'servicesByCategory' => $servicesByCategory,
            'availability' => $availability,
            'reviews' => $reviews,
            'reviewStats' => [
                'total' => $provider->rating_count,
                'average' => $provider->rating_avg,
                'average_display' => $provider->rating_display,
                'distribution' => $distribution,
            ],
        ]);
    }
}
