<?php

namespace App\Http\Middleware;

use App\Domains\Provider\Enums\TeamPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTeamPermission
{
    /**
     * Handle an incoming request.
     *
     * Check if the user has the required permission(s) for the provider.
     * The provider is expected to be bound in the request via middleware.
     *
     * Usage: middleware('permission:view_bookings') or middleware('permission:view_bookings,manage_bookings')
     *
     * @param  string  ...$permissions  One or more permission keys (user needs at least one)
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return $this->unauthorized($request, 'Authentication required.');
        }

        // Get the provider from the authenticated user
        $provider = $user->provider;

        if (! $provider) {
            // User might be accessing as a team member, try to get from request
            $provider = $request->attributes->get('provider');
        }

        if (! $provider) {
            return $this->unauthorized($request, 'No provider context found.');
        }

        // Owner has all permissions
        if ($provider->isOwner($user)) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $userPermissions = $provider->getPermissionsFor($user);

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                return $next($request);
            }
        }

        return $this->unauthorized($request, 'You do not have permission to perform this action.');
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 403);
        }

        return redirect()->route('provider.dashboard')->with('error', $message);
    }
}
