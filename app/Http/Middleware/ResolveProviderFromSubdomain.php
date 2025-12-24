<?php

namespace App\Http\Middleware;

use App\Domains\Provider\Models\Provider;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveProviderFromSubdomain
{
    public function handle(Request $request, Closure $next): Response
    {
        // $host = $request->getHost();
        // $appDomain = $this->getBaseDomain();

        // // Extract subdomain by removing the app domain
        // $subdomain = str_replace('.' . $appDomain, '', $host);

        // // If no subdomain found (same as host) or empty, abort
        // if ($subdomain === $host || empty($subdomain)) {
        //     abort(404, 'Provider not found');
        // }


        $slug = $request->provider;


        // Find provider by slug
        $provider = Provider::where('slug', $slug)
            ->with(['user', 'services' => function ($query) {
                $query->where('is_active', true)->orderBy('name');
            }])
            ->first();

        if (!$provider) {
            abort(404, 'Provider not found');
        }

        // Share provider globally via service container
        app()->instance('providersite.provider', $provider);

        // Also bind the slug for route parameter resolution
        $request->route()->setParameter('provider', $slug);

        return $next($request);
    }

    /**
     * Get the base domain without port.
     */
    protected function getBaseDomain(): string
    {
        $domain = config('app.domain');

        // Remove port if present (e.g., lvh.me:8000 -> lvh.me)
        if (str_contains($domain, ':')) {
            $domain = explode(':', $domain)[0];
        }

        return $domain;
    }
}
