<?php

namespace App\Http\Middleware;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Provider\Models\Provider;
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
        // Helper to get provider - evaluated lazily after providersite middleware runs
        $getProvider = fn () => app()->bound('site.provider') ? app('site.provider') : null;

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
            // Provider site context (lazy evaluated after route middleware runs)
            'isProviderSite' => fn () => (bool) $getProvider(),
            '__provider' => function () use ($getProvider) {
                $provider = $getProvider();

                return $provider instanceof Provider ? [
                    'id' => $provider->id,
                    'business_name' => $provider->business_name,
                    'domain' => $provider->domain,
                    'avatar' => $provider->getAvatarUrlAttribute(),
                    'cover_image' => $provider->getCoverPhotoUrlAttribute(),
                    // Branding
                    'brand_primary_color' => $provider->brand_primary_color,
                    'brand_primary_rgb' => $provider->brand_primary_rgb,
                    'brand_hover_color' => $provider->brand_hover_color,
                    'brand_text_color' => $provider->brand_text_color,
                    'brand_success_color' => $provider->brand_success_color,
                    'brand_warning_color' => $provider->brand_warning_color,
                    'brand_danger_color' => $provider->brand_danger_color,
                    'brand_info_color' => $provider->brand_info_color,
                    'brand_secondary_color' => $provider->brand_secondary_color,
                ] : null;
            },
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
            // Launch mode status for conditional UI rendering
            'launchModeEnabled' => (bool) SystemSetting::get('launch_mode_enabled', false),
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
