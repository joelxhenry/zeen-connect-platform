<?php

namespace App\Http\Middleware;

use App\Domains\Admin\Models\SystemSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLaunchMode
{
    /**
     * Routes to block during launch mode.
     */
    protected array $blockedRoutes = [
        'register',
        'register.provider',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if launch mode is enabled
        if (! SystemSetting::get('launch_mode_enabled', false)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // Block registration routes during launch mode
        if (in_array($routeName, $this->blockedRoutes)) {
            return redirect()->route('founding-members');
        }

        return $next($request);
    }
}
