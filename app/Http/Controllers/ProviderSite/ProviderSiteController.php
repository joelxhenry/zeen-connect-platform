<?php

namespace App\Http\Controllers\ProviderSite;

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
}
