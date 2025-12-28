<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Resources\ProviderResource;
use App\Domains\Provider\Resources\ProviderSimpleResource;
use App\Domains\Subscription\Enums\SubscriptionTier;
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

        $providers->getCollection()->transform(
            fn ($provider) => (new ProviderSimpleResource($provider))->resolve()
        );

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
                'services.category',
                'availability',
            ])
            ->withCount(['reviews', 'services'])
            ->firstOrFail();

        return Inertia::render('Admin/Providers/Show', [
            'provider' => (new ProviderResource($provider))
                ->withUser(true)
                ->withServices(true)
                ->withAdminDetails(true)
                ->withCounts(true)
                ->resolve(),
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

    /**
     * Mark a provider as a founding member.
     * Uses their current subscription tier to determine perks.
     */
    public function markAsFoundingMember(string $uuid): RedirectResponse
    {
        $provider = Provider::where('uuid', $uuid)->firstOrFail();

        $tier = $provider->getTier();

        // Only Premium and Enterprise tiers are eligible
        if (! $tier->isFoundingEligible()) {
            return back()->with('error', 'Provider must be on Premium or Enterprise tier to become a founding member.');
        }

        $provider->update([
            'is_founding_member' => true,
            'founding_member_at' => now(),
        ]);

        $waiverEndsAt = $provider->getFoundingFeeWaiverEndsAt();
        $tierLabel = $tier === SubscriptionTier::ENTERPRISE ? 'Enterprise' : 'Growth';

        return back()->with('success', "Provider marked as {$tierLabel} founding member. Fee waiver ends {$waiverEndsAt->format('M j, Y')}.");
    }

    /**
     * Remove founding member status from a provider.
     */
    public function removeFoundingMember(string $uuid): RedirectResponse
    {
        $provider = Provider::where('uuid', $uuid)->firstOrFail();

        $provider->update([
            'is_founding_member' => false,
            'founding_member_at' => null,
        ]);

        return back()->with('success', 'Founding member status removed.');
    }
}
