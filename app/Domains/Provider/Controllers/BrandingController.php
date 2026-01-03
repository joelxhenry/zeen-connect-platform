<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Industry\Models\Industry;
use App\Domains\Provider\Requests\UpdateBrandingSettingsRequest;
use App\Domains\ProviderSite\Enums\TemplateType;
use App\Domains\Subscription\Enums\SubscriptionFeature;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(): Response
    {
        $provider = Auth::user()->provider;
        $canAccess = $provider->hasFeature(SubscriptionFeature::BRANDING);
        $currentTier = $provider->getTier();

        // Media
        $logoMedia = $provider->getFirstMedia('logo');
        $coverMedia = $provider->getFirstMedia('cover');
        $galleryMedia = $provider->getMedia('gallery')->map->toMediaArray();

        // Templates
        $templates = collect(TemplateType::allWithMetadata())->map(function ($template) use ($provider, $currentTier) {
            $templateType = TemplateType::from($template['value']);
            return [
                ...$template,
                'is_available' => $templateType->isAvailableForTier($currentTier),
                'is_selected' => $provider->site_template === $template['value'],
            ];
        });

        // Industries
        $industries = Industry::active()->ordered()->get(['id', 'name', 'icon']);

        return Inertia::render('Provider/Branding/Edit', [
            // Access control
            'canAccess' => $canAccess,
            'currentTier' => $currentTier->value,
            'currentTierLabel' => $currentTier->label(),

            // Visuals - Colors (primary, secondary, accent, and color mode)
            'brandSettings' => [
                'primary_color' => $provider->brand_primary_color,
                'secondary_color' => $provider->brand_secondary_color,
                'accent_color' => $provider->brand_accent_color,
                'color_mode' => $provider->color_mode ?? 'system',
            ],
            'defaultColors' => [
                'primary_color' => '#106B4F',
                'secondary_color' => '#6B7280',
                'accent_color' => '#8B5CF6',
            ],

            // Visuals - Media
            'logo' => $logoMedia?->toMediaArray(),
            'cover' => $coverMedia?->toMediaArray(),
            'gallery' => $galleryMedia,

            // Visuals - Templates
            'templates' => $templates,
            'currentTemplate' => $provider->site_template ?? 'default',

            // Content
            'content' => [
                'business_name' => $provider->business_name,
                'industry_id' => $provider->industry_id,
                'bio' => $provider->bio,
                'tagline' => $provider->tagline,
                'address' => $provider->address,
                'website' => $provider->website,
                'social_links' => $provider->social_links ?? [],
            ],
            'industries' => $industries,

            // Domain
            'domain' => $provider->domain,
            'siteUrl' => $provider->public_url,
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

    public function updateContent(Request $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $validated = $request->validate([
            'business_name' => ['required', 'string', 'min:2', 'max:100'],
            'industry_id' => ['nullable', 'exists:industries,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.twitter' => ['nullable', 'string', 'max:255'],
            'social_links.linkedin' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
        ]);

        // Filter out empty social links
        $socialLinks = array_filter($validated['social_links'] ?? [], fn ($value) => ! empty($value));

        $provider->update([
            'business_name' => $validated['business_name'],
            'industry_id' => $validated['industry_id'],
            'bio' => $validated['bio'],
            'tagline' => $validated['tagline'],
            'address' => $validated['address'],
            'website' => $validated['website'],
            'social_links' => empty($socialLinks) ? null : $socialLinks,
        ]);

        return redirect()
            ->route('provider.branding.edit')
            ->with('success', 'Content updated successfully.');
    }

    public function updateTemplate(Request $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $validated = $request->validate([
            'template' => ['required', 'string', 'in:' . implode(',', array_column(TemplateType::cases(), 'value'))],
        ]);

        $template = TemplateType::from($validated['template']);

        // Check if provider can use this template
        if (!$provider->canUseTemplate($template)) {
            return redirect()
                ->route('provider.branding.edit')
                ->withErrors(['template' => "The {$template->label()} template requires a {$template->requiredTier()->label()} subscription or higher."]);
        }

        $provider->update([
            'site_template' => $template->value,
        ]);

        return redirect()
            ->route('provider.branding.edit')
            ->with('success', 'Template updated successfully.');
    }

    public function updateDomain(Request $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $validated = $request->validate([
            'domain' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:providers,domain,' . $provider->id,
            ],
        ], [
            'domain.regex' => 'The domain must contain only lowercase letters, numbers, and hyphens.',
            'domain.unique' => 'This domain is already taken. Please choose another.',
        ]);

        $provider->update([
            'domain' => $validated['domain'],
        ]);

        return redirect()
            ->route('provider.branding.edit')
            ->with('success', 'Domain updated successfully.');
    }

    public function checkDomain(Request $request): \Illuminate\Http\JsonResponse
    {
        $domain = $request->input('domain');
        $providerId = Auth::user()->provider->id;

        $exists = \App\Domains\Provider\Models\Provider::where('domain', $domain)
            ->where('id', '!=', $providerId)
            ->exists();

        return response()->json([
            'available' => !$exists,
            'domain' => $domain,
        ]);
    }
}
