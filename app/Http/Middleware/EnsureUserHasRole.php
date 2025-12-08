<?php

namespace App\Http\Middleware;

use App\Domains\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        $userRole = $request->user()->role;

        foreach ($roles as $role) {
            $requiredRole = UserRole::tryFrom($role);

            if ($requiredRole && $userRole === $requiredRole) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. Required role: ' . implode(' or ', $roles)], 403);
        }

        abort(403, 'Unauthorized action.');
    }
}
