<?php

namespace App\Http\Middleware;

use App\Domains\User\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsClient
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== UserRole::Client) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Client access required.'], 403);
            }

            return redirect()->route('home')->with('error', 'Access denied. Client account required.');
        }

        return $next($request);
    }
}
