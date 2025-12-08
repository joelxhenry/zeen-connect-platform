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
        if (! $request->user() || $request->user()->role !== UserRole::Provider) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized. Provider access required.'], 403);
            }

            return redirect()->route('home')->with('error', 'Access denied. Provider account required.');
        }

        return $next($request);
    }
}
