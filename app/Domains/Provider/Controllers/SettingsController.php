<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\UpdateProviderBookingSettingsAction;
use App\Domains\Provider\Requests\UpdateProviderBookingSettingsRequest;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private UpdateProviderBookingSettingsAction $updateBookingSettingsAction,
        private SubscriptionService $subscriptionService,
    ) {}

    public function edit(): Response
    {
        $provider = Auth::user()->provider;

        return Inertia::render('Provider/Settings/Edit', [
            'bookingSettings' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'feePayer' => $provider->fee_payer ?? 'provider',
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
        ]);
    }

    public function updateBookingSettings(UpdateProviderBookingSettingsRequest $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $this->updateBookingSettingsAction->execute($provider, $request->validated());

        return redirect()
            ->route('provider.settings.edit')
            ->with('success', 'Booking settings updated successfully.');
    }
}
