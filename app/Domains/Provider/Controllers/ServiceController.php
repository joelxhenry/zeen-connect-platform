<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\CreateServiceAction;
use App\Domains\Provider\Actions\UpdateServiceAction;
use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Requests\StoreServiceRequest;
use App\Domains\Provider\Requests\UpdateServiceRequest;
use App\Domains\Service\Models\Service;
use App\Domains\Service\Resources\CategoryResource;
use App\Domains\Service\Resources\ServiceResource;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        private CreateServiceAction $createAction,
        private UpdateServiceAction $updateAction,
        private SubscriptionService $subscriptionService,
    ) {}

    public function index(): Response
    {
        $provider = Auth::user()->provider;
        $services = $provider->services()
            ->with(['categories', 'media'])
            ->withCount(['bookings as total_bookings'])
            ->ordered()
            ->get();

        // Get service statistics
        $stats = [
            'total' => $services->count(),
            'active' => $services->where('is_active', true)->count(),
            'inactive' => $services->where('is_active', false)->count(),
        ];

        // Get provider's service categories for filtering
        $categories = $provider->serviceCategories()->active()->ordered()->get();

        return Inertia::render('Provider/Services/Index', [
            'services' => $services->map(fn ($s) => (new ServiceResource($s))->withCategories()->withMedia()->withCounts()->resolve()),
            'stats' => $stats,
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
            'providerDefaults' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
        ]);
    }

    public function create(): Response
    {
        $provider = Auth::user()->provider;

        // Get provider's service categories
        $categories = $provider->serviceCategories()->active()->ordered()->get();

        // Get active team members for assignment
        $teamMembers = $provider->teamMembers()
            ->where('status', TeamMemberStatus::ACTIVE)
            ->get()
            ->map(fn ($tm) => [
                'id' => $tm->id,
                'name' => $tm->display_name,
                'avatar' => $tm->avatar,
            ]);

        return Inertia::render('Provider/Services/Create', [
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
            'providerDefaults' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
            'teamMembers' => $teamMembers,
        ]);
    }

    public function store(StoreServiceRequest $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $this->createAction->execute($provider, $request->validated());

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): Response
    {
        $provider = Auth::user()->provider;

        // Ensure the service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            abort(403);
        }

        // Get provider's service categories
        $categories = $provider->serviceCategories()->active()->ordered()->get();

        // Load media relationships and team members
        $service->load(['categories', 'media', 'videoEmbeds', 'teamMembers']);

        // Get active team members for assignment
        $teamMembers = $provider->teamMembers()
            ->where('status', TeamMemberStatus::ACTIVE)
            ->get()
            ->map(fn ($tm) => [
                'id' => $tm->id,
                'name' => $tm->display_name,
                'avatar' => $tm->avatar,
            ]);

        return Inertia::render('Provider/Services/Edit', [
            'service' => (new ServiceResource($service))
                ->withCategories()
                ->withMedia()
                ->withBookingSettings()
                ->withTeamMembers()
                ->resolve(),
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
            'providerDefaults' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
            'providerSubdomain' => $provider->subdomain,
            'teamMembers' => $teamMembers,
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            abort(403);
        }

        $this->updateAction->execute($service, $request->validated());

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            abort(403);
        }

        $service->delete();

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service deleted successfully.');
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            abort(403);
        }

        $service->update(['is_active' => ! $service->is_active]);

        $status = $service->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('provider.services.index')
            ->with('success', "Service {$status} successfully.");
    }

}
