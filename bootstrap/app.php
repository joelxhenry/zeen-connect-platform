<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsClient;
use App\Http\Middleware\EnsureUserIsProvider;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveProviderFromSubdomain;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

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

            // Admin domain routes
            Route::domain(config('app.admin_domain'))
                ->middleware(['web', 'auth', 'role:admin'])
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
        $middleware->web(append: [
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
        ]);

        $middleware->throttleApi();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
