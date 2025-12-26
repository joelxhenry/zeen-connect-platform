<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AddXsrfTokenCookie
{
    /**
     * Handle an incoming request.
     *
     * Add the XSRF-TOKEN cookie with the correct domain for cross-subdomain sharing.
     */
    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        $response = $next($request);

        if ($this->shouldAddXsrfTokenCookie($request, $response)) {
            $this->addCookieToResponse($request, $response);
        }

        return $response;
    }

    /**
     * Determine if the cookie should be added to the response.
     */
    protected function shouldAddXsrfTokenCookie(Request $request, SymfonyResponse $response): bool
    {
        return $request->hasSession() && ! $request->session()->has('_token_refreshed');
    }

    /**
     * Add the XSRF-TOKEN cookie to the response.
     */
    protected function addCookieToResponse(Request $request, SymfonyResponse $response): void
    {
        $token = $request->session()->token();

        $cookie = new Cookie(
            'XSRF-TOKEN',
            $token,
            time() + 60 * config('session.lifetime'),
            config('session.path'),
            config('session.domain'),
            config('session.secure'),
            false, // httpOnly must be false for JavaScript to read
            false,
            config('session.same_site', 'lax')
        );

        $response->headers->setCookie($cookie);
    }
}
