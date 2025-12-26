<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $isProviderSite = app()->bound('providersite.provider');
        $providerSiteProvider = $isProviderSite ? app('providersite.provider') : null;

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'csrf_token' => csrf_token(),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Provider site context
            'isProviderSite' => $isProviderSite,
            'providerSiteProvider' => $providerSiteProvider ? [
                'id' => $providerSiteProvider->id,
                'business_name' => $providerSiteProvider->business_name,
                'slug' => $providerSiteProvider->slug,
                'avatar' => $providerSiteProvider->user?->avatar,
                'cover_image' => $providerSiteProvider->cover_image,
            ] : null,
            // Domain URLs
            'domains' => [
                'main' => config('app.url'),
                'admin' => $this->buildDomainUrl(config('app.admin_domain')),
                'console' => $this->buildDomainUrl(config('app.console_domain')),
                'auth' => $this->buildDomainUrl(config('app.auth_domain')),
                'payments' => $this->buildDomainUrl(config('app.payments_domain')),
            ],
            'mainPlatformUrl' => config('app.url'),
            'appDomain' => config('app.domain'),
        ];
    }

    /**
     * Build a full URL for a domain, using http for local development.
     */
    protected function buildDomainUrl(string $domain): string
    {
        $scheme = app()->environment('local') ? 'http' : 'https';
        $port = app()->environment('local') ? ':8000' : '';

        return "{$scheme}://{$domain}{$port}";
    }
}
