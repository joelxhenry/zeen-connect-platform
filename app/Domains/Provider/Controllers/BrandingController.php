<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Requests\UpdateBrandingSettingsRequest;
use App\Domains\Subscription\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        $provider = Auth::user()->provider;
        $canAccess = $provider->hasFeature(SubscriptionFeature::BRANDING);

        return Inertia::render('Provider/Branding/Edit', [
            'canAccess' => $canAccess,
            'currentTier' => $provider->getTier()->value,
            'currentTierLabel' => $provider->getTier()->label(),
            'brandSettings' => [
                'primary_color' => $provider->brand_primary_color,
                'text_color' => $provider->brand_text_color,
                'success_color' => $provider->brand_success_color,
                'warning_color' => $provider->brand_warning_color,
                'danger_color' => $provider->brand_danger_color,
                'info_color' => $provider->brand_info_color,
                'secondary_color' => $provider->brand_secondary_color,
            ],
            'defaultColors' => [
                'primary_color' => '#106B4F',
                'text_color' => '#0D1F1B',
                'success_color' => '#22C55E',
                'warning_color' => '#F59E0B',
                'danger_color' => '#EF4444',
                'info_color' => '#3B82F6',
                'secondary_color' => '#6B7280',
            ],
        ]);
    }

    public function update(UpdateBrandingSettingsRequest $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Store as JSON object
        $provider->update([
            'branding' => $request->validated(),
        ]);

        return redirect()
            ->route('provider.branding.edit')
            ->with('success', 'Branding settings updated successfully.');
    }
}
