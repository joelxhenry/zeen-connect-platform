<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Event\Models\Event;
use App\Domains\Provider\Models\Provider;
use App\Domains\ProviderSite\Services\ProviderSiteDataService;
use App\Domains\ProviderSite\Services\TemplateResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSiteController extends Controller
{
    public function __construct(
        protected ProviderSiteDataService $dataService,
        protected TemplateResolver $templateResolver
    ) {}

    /**
     * Get the provider from the provider site middleware.
     */
    protected function getProvider(): Provider
    {
        return app('site.provider');
    }

    /**
     * Display the provider's homepage.
     */
    public function home(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getHomePageData($provider);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Home'),
            $data
        );
    }

    /**
     * Display the provider's services page.
     */
    public function services(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getServicesPageData($provider);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Services'),
            $data
        );
    }

    /**
     * Display the provider's reviews page.
     */
    public function reviews(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getReviewsPageData($provider);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Reviews'),
            $data
        );
    }

    /**
     * Display the provider's events listing page.
     */
    public function events(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getEventsPageData($provider);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Events'),
            $data
        );
    }

    /**
     * Display a single event with its occurrences.
     */
    public function showEvent(Request $request, string $slug): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);

        // Find the event by slug for this provider
        $event = Event::where('provider_id', $provider->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $this->dataService->getEventDetailData($provider, $event);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'EventDetail'),
            $data
        );
    }
}
