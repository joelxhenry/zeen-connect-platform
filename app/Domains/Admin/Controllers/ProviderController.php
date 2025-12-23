<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Provider\Models\Provider;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    /**
     * Display a listing of providers.
     */
    public function index(Request $request): Response
    {
        $query = Provider::query()
            ->with([
                'user:id,name,email,avatar',
                'primaryLocation:id,name,region_id',
                'primaryLocation.region:id,name',
            ])
            ->withCount(['services', 'reviews']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by featured
        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured === 'yes');
        }

        // Search by business name or user email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');

        if ($sortBy === 'rating') {
            $query->orderByDesc('rating_avg')->orderByDesc('rating_count');
        } elseif ($sortBy === 'bookings') {
            $query->orderByDesc('total_bookings');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        $providers = $query->paginate(20)->withQueryString();

        $providers->getCollection()->transform(fn ($provider) => [
            'id' => $provider->id,
            'uuid' => $provider->uuid,
            'slug' => $provider->slug,
            'business_name' => $provider->business_name,
            'user' => [
                'name' => $provider->user->name,
                'email' => $provider->user->email,
                'avatar' => $provider->user->avatar,
            ],
            'location' => $provider->primaryLocation?->display_name,
            'status' => $provider->status,
            'is_featured' => $provider->is_featured,
            'rating_avg' => $provider->rating_avg,
            'rating_count' => $provider->rating_count,
            'total_bookings' => $provider->total_bookings,
            'services_count' => $provider->services_count,
            'reviews_count' => $provider->reviews_count,
            'commission_rate' => $provider->commission_rate,
            'verified_at' => $provider->verified_at?->format('M d, Y'),
            'created_at' => $provider->created_at->format('M d, Y'),
        ]);

        return Inertia::render('Admin/Providers/Index', [
            'providers' => $providers,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'featured' => $request->featured,
                'sort' => $sortBy,
                'dir' => $sortDir,
            ],
            'statuses' => [
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'suspended', 'label' => 'Suspended'],
            ],
        ]);
    }

    /**
     * Display the specified provider.
     */
    public function show(string $uuid): Response
    {
        $provider = Provider::where('uuid', $uuid)
            ->with([
                'user:id,name,email,phone,avatar,created_at',
                'primaryLocation.region',
                'locations',
                'services.category',
                'availability',
            ])
            ->withCount(['reviews', 'services'])
            ->firstOrFail();

        return Inertia::render('Admin/Providers/Show', [
            'provider' => [
                'id' => $provider->id,
                'uuid' => $provider->uuid,
                'slug' => $provider->slug,
                'business_name' => $provider->business_name,
                'tagline' => $provider->tagline,
                'bio' => $provider->bio,
                'website' => $provider->website,
                'social_links' => $provider->social_links,
                'status' => $provider->status,
                'is_featured' => $provider->is_featured,
                'commission_rate' => $provider->commission_rate,
                'rating_avg' => $provider->rating_avg,
                'rating_count' => $provider->rating_count,
                'total_bookings' => $provider->total_bookings,
                'services_count' => $provider->services_count,
                'reviews_count' => $provider->reviews_count,
                'verified_at' => $provider->verified_at?->format('M d, Y H:i'),
                'created_at' => $provider->created_at->format('M d, Y H:i'),
                'user' => [
                    'id' => $provider->user->id,
                    'name' => $provider->user->name,
                    'email' => $provider->user->email,
                    'phone' => $provider->user->phone,
                    'avatar' => $provider->user->avatar,
                    'joined' => $provider->user->created_at->format('M d, Y'),
                ],
                'location' => $provider->primaryLocation ? [
                    'name' => $provider->primaryLocation->name,
                    'region' => $provider->primaryLocation->region->name,
                ] : null,
                'locations' => $provider->locations->map(fn ($loc) => [
                    'id' => $loc->id,
                    'name' => $loc->name,
                ]),
                'services' => $provider->services->map(fn ($service) => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'category' => $service->category->name,
                    'price' => $service->price_display,
                    'duration' => $service->duration_display,
                    'is_active' => $service->is_active,
                ]),
            ],
        ]);
    }

    /**
     * Update provider status (activate, suspend, etc.).
     */
    public function updateStatus(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'status' => 'required|in:pending,active,suspended',
        ]);

        $provider = Provider::where('uuid', $uuid)->firstOrFail();

        $data = ['status' => $request->status];

        // Set verified_at when activating
        if ($request->status === 'active' && ! $provider->verified_at) {
            $data['verified_at'] = now();
        }

        $provider->update($data);

        return back()->with('success', 'Provider status updated successfully.');
    }

    /**
     * Toggle featured status.
     */
    public function toggleFeatured(string $uuid): RedirectResponse
    {
        $provider = Provider::where('uuid', $uuid)->firstOrFail();

        $provider->update(['is_featured' => ! $provider->is_featured]);

        $status = $provider->is_featured ? 'featured' : 'unfeatured';

        return back()->with('success', "Provider {$status} successfully.");
    }

    /**
     * Update commission rate.
     */
    public function updateCommission(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $provider = Provider::where('uuid', $uuid)->firstOrFail();

        $provider->update(['commission_rate' => $request->commission_rate]);

        return back()->with('success', 'Commission rate updated successfully.');
    }
}
