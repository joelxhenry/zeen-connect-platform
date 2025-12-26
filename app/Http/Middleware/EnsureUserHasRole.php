<?php

namespace App\Http\Middleware;

use App\Domains\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->to($this->getLoginRoute($request, $roles));
        }

        $userRole = $request->user()->role;

        foreach ($roles as $role) {
            $requiredRole = UserRole::tryFrom($role);

            if ($requiredRole && $userRole === $requiredRole) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. Required role: '.implode(' or ', $roles)], 403);
        }

        abort(403, 'Unauthorized action.');
    }

    /**
     * Get the appropriate login route based on the required roles.
     */
    private function getLoginRoute(Request $request, array $roles): string
    {
        $scheme = $request->secure() ? 'https' : 'http';
        $port = $request->getPort();
        $portSuffix = ($port && $port !== 80 && $port !== 443) ? ':' . $port : '';

        // If admin role is required, redirect to admin login
        if (in_array('admin', $roles)) {
            return $scheme . '://' . config('app.admin_domain') . $portSuffix . '/login';
        }

        // Default to the auth domain login route
        return $scheme . '://' . config('app.auth_domain') . $portSuffix . '/login';
    }
}
