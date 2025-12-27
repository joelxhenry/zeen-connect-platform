<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Provider\Actions\CreateServiceAction;
use App\Domains\Provider\Actions\UpdateServiceAction;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Requests\StoreServiceRequest;
use App\Domains\Provider\Requests\UpdateServiceRequest;
use App\Domains\Service\Models\Category;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with('category')
            ->ordered()
            ->get();

        return Inertia::render('Provider/Services/Index', [
            'services' => $services,
            'providerDefaults' => $provider->getBookingSettings(),
        ]);
    }

    public function create(): Response
    {
        $provider = Auth::user()->provider;
        $categories = Category::active()->ordered()->get();

        return Inertia::render('Provider/Services/Create', [
            'categories' => $categories,
            'providerDefaults' => $provider->getBookingSettings(),
            'feeInfo' => $this->getFeeInfo($provider),
        ]);
    }

    private function getFeeInfo(Provider $provider): array
    {
        return [
            'tier' => $provider->getTier()->value,
            'tier_label' => $provider->getTier()->label(),
            'deposit_percentage' => $this->subscriptionService->getEffectiveDepositPercentage($provider),
            'platform_fee_rate' => $this->subscriptionService->getPlatformFeeRate($provider) * 100,
            'processing_fee_rate' => (float) SystemSetting::get('enterprise_processing_fee_rate', 2.9),
            'processing_fee_flat' => (float) SystemSetting::get('enterprise_processing_fee_flat', 50),
            'processing_fee_payer' => $provider->processing_fee_payer,
        ];
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

        $categories = Category::active()->ordered()->get();

        // Load media relationships
        $service->load(['category', 'media', 'displayMedia']);

        // Prepare service data with media
        $serviceData = $service->toArray();
        $serviceData['gallery'] = $service->getMedia('gallery')->map->toMediaArray()->toArray();
        $serviceData['display_media_id'] = $service->display_media_id;

        return Inertia::render('Provider/Services/Edit', [
            'service' => $serviceData,
            'categories' => $categories,
            'providerDefaults' => $provider->getBookingSettings(),
            'feeInfo' => $this->getFeeInfo($provider),
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

    /**
     * Set the display image for a service.
     */
    public function setDisplayImage(Request $request, Service $service): JsonResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $mediaId = $request->input('media_id');

        // Validate the media belongs to this service
        if ($mediaId) {
            $media = $service->media()->where('id', $mediaId)->first();
            if (! $media) {
                return response()->json(['error' => 'Media not found for this service'], 404);
            }
        }

        $service->update(['display_media_id' => $mediaId]);

        return response()->json([
            'success' => true,
            'display_media_id' => $mediaId,
        ]);
    }
}
