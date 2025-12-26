<?php

namespace App\Http\Middleware;

use App\Domains\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsProvider
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Provider) {
            return $this->unauthorized($request);
        }

        // Check if user owns a provider profile
        if ($user->provider) {
            // User is the owner of their provider
            $request->attributes->set('provider', $user->provider);
            $request->attributes->set('is_owner', true);

            return $next($request);
        }

        // Check if user is a team member of any provider
        $teamMembership = $user->activeTeamMemberships()->with('provider')->first();

        if ($teamMembership && $teamMembership->provider) {
            // User is a team member
            $request->attributes->set('provider', $teamMembership->provider);
            $request->attributes->set('is_owner', false);
            $request->attributes->set('team_member', $teamMembership);

            return $next($request);
        }

        return $this->unauthorized($request);
    }

    protected function unauthorized(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. Provider access required.'], 403);
        }

        return redirect()->route('home')->with('error', 'Access denied. Provider account required.');
    }
}
