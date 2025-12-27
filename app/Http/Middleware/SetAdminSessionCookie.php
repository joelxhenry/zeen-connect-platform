<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SetAdminSessionCookie
{
    /**
     * Handle an incoming request.
     *
     * Set a separate session cookie for the admin domain to isolate
     * admin sessions from provider/client sessions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $adminDomain = config('app.admin_domain');

        // If on admin domain, use a separate session cookie
        if ($host === $adminDomain) {
            $appName = Str::slug(config('app.name', 'laravel'));
            config([
                'session.cookie' => $appName . '-admin-session',
                'session.domain' => $adminDomain,
            ]);
        }

        return $next($request);
    }
}
