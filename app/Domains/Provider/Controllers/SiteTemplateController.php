<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\ProviderSite\Enums\TemplateType;
use App\Domains\ProviderSite\Services\TemplateResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SiteTemplateController extends Controller
{
    public function __construct(
        protected TemplateResolver $templateResolver
    ) {}

    public function edit(): Response
    {
        $provider = Auth::user()->provider;
        $currentTier = $provider->getTier();

        $templates = collect(TemplateType::allWithMetadata())->map(function ($template) use ($provider, $currentTier) {
            $templateType = TemplateType::from($template['value']);
            return [
                ...$template,
                'is_available' => $templateType->isAvailableForTier($currentTier),
                'is_selected' => $provider->site_template === $template['value'],
            ];
        });

        return Inertia::render('Provider/Settings/SiteTemplate', [
            'templates' => $templates,
            'currentTemplate' => $provider->site_template ?? 'default',
            'currentTier' => $currentTier->value,
            'currentTierLabel' => $currentTier->label(),
            'siteUrl' => $provider->getUrl(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'template' => ['required', 'string', 'in:' . implode(',', array_column(TemplateType::cases(), 'value'))],
        ]);

        $provider = Auth::user()->provider;
        $template = TemplateType::from($request->template);

        // Check if provider can use this template
        if (!$provider->canUseTemplate($template)) {
            return redirect()
                ->route('provider.site.template.edit')
                ->withErrors(['template' => "The {$template->label()} template requires a {$template->requiredTier()->label()} subscription or higher."]);
        }

        $provider->update([
            'site_template' => $template->value,
        ]);

        return redirect()
            ->route('provider.site.template.edit')
            ->with('success', 'Site template updated successfully.');
    }
}
