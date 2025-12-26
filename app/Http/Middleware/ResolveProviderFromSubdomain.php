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
        // Get provider slug from subdomain route parameter
        // The {provider} in Route::domain('{provider}.zeen.com') binds to this
        $slug = $request->route('provider');

        if (!$slug) {
            abort(404, 'Provider not found');
        }

        // Find provider by slug
        $provider = Provider::where('slug', $slug)
            ->with(['user', 'services' => fn($query) => $query->where('is_active', true)->orderBy('name')])
            ->first();

        if (!$provider) {
            abort(404, 'Provider not found');
        }

        // Share provider globally via service container
        app()->instance('site.provider', $provider);

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
