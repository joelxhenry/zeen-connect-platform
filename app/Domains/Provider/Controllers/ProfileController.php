<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Location\Models\Country;
use App\Domains\Provider\Actions\UpdateProviderProfileAction;
use App\Domains\Provider\Requests\UpdateProviderProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private UpdateProviderProfileAction $updateProfileAction,
    ) {}

    /**
     * Show the provider profile edit form.
     */
    public function edit(): Response
    {
        $user = Auth::user();
        $provider = $user->provider()->with(['primaryLocation.region.country', 'locations'])->first();

        // Get countries with regions and locations for the location selector
        $countries = Country::with(['regions.locations' => fn ($q) => $q->active()])
            ->whereHas('regions.locations', fn ($q) => $q->active())
            ->orderBy('name')
            ->get();

        return Inertia::render('Provider/Profile/Edit', [
            'provider' => $provider,
            'countries' => $countries,
        ]);
    }

    /**
     * Update the provider profile.
     */
    public function update(UpdateProviderProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;

        $this->updateProfileAction->execute($provider, $request->validated());

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }
}
