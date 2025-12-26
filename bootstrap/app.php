<?php

use App\Http\Middleware\AddXsrfTokenCookie;
use App\Http\Middleware\CheckLaunchMode;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsClient;
use App\Http\Middleware\EnsureUserIsProvider;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveProviderFromSubdomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Auth domain routes
            Route::domain(config('app.auth_domain'))
                ->middleware('web')
                ->group(base_path('routes/auth.php'));

            // Payments domain routes
            Route::domain(config('app.payments_domain'))
                ->middleware('web')
                ->group(base_path('routes/payment.php'));

            // Admin domain routes (auth handled in routes file)
            Route::domain(config('app.admin_domain'))
                ->middleware('web')
                ->group(base_path('routes/admin.php'));

            // Provider console domain routes
            Route::domain(config('app.console_domain'))
                ->middleware(['web', 'auth', 'role:provider'])
                ->group(base_path('routes/provider.php'));

            // Provider site subdomain routes (dynamic)
            Route::domain('{provider}.' . config('app.domain'))
                ->middleware(['web', 'providersite'])
                ->group(base_path('routes/provider-site.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            HandleCors::class,
        ], append: [
            AddXsrfTokenCookie::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'provider' => EnsureUserIsProvider::class,
            'client' => EnsureUserIsClient::class,
            'providersite' => ResolveProviderFromSubdomain::class,
            'launch.mode' => CheckLaunchMode::class,
        ]);

        // Redirect authenticated users away from guest routes based on role
        $middleware->redirectUsersTo(function (Request $request) {
            $user = $request->user();
            if (!$user) {
                return route('home');
            }

            $scheme = $request->secure() ? 'https' : 'http';
            $port = $request->getPort();
            $portSuffix = ($port && $port !== 80 && $port !== 443) ? ':' . $port : '';

            return match ($user->role->value) {
                'admin' => $scheme . '://' . config('app.admin_domain') . $portSuffix . '/',
                'provider' => $scheme . '://' . config('app.console_domain') . $portSuffix . '/',
                default => route('client.dashboard'),
            };
        });

        // Redirect guests to login (preserving port for local development)
        $middleware->redirectGuestsTo(function (Request $request) {
            $scheme = $request->secure() ? 'https' : 'http';
            $port = $request->getPort();
            $portSuffix = ($port && $port !== 80 && $port !== 443) ? ':' . $port : '';

            return $scheme . '://' . config('app.auth_domain') . $portSuffix . '/login';
        });

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Render custom error pages via Inertia
        $exceptions->render(function (Throwable $e, Request $request) {
            // Handle HTTP exceptions (404, 403, 419, 500, 503)
            if ($e instanceof HttpExceptionInterface) {
                $statusCode = $e->getStatusCode();

                // For Inertia/web requests, render Vue error pages
                if ($request->header('X-Inertia') || ! $request->expectsJson()) {
                    $page = match ($statusCode) {
                        404 => 'Error/404',
                        403 => 'Error/403',
                        419 => 'Error/419',
                        503 => 'Error/503',
                        default => 'Error/500',
                    };

                    return Inertia::render($page, [
                        'status' => $statusCode,
                        'message' => $e->getMessage() ?: null,
                    ])->toResponse($request)->setStatusCode($statusCode);
                }
            }

            // For API requests, return consistent JSON error format
            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => $statusCode,
                        'message' => $e->getMessage() ?: 'An unexpected error occurred',
                        'type' => class_basename($e),
                    ],
                ], $statusCode);
            }

            return null;
        });

        // Report to Sentry if available
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('sentry') && app()->environment('production')) {
                app('sentry')->captureException($e);
            }
        });
    })->create();
