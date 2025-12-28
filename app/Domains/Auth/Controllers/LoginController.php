<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LoginController extends Controller
{
    public function __construct(
        private LoginUserAction $loginAction,
        private LogoutUserAction $logoutAction,
    ) {}

    /**
     * Build a cross-domain URL preserving the current port.
     */
    private function buildDomainUrl(Request $request, string $domain, string $path = '/'): string
    {
        $scheme = $request->secure() ? 'https' : 'http';
        $port = $request->getPort();
        $portSuffix = ($port && $port !== 80 && $port !== 443) ? ':' . $port : '';

        return $scheme . '://' . $domain . $portSuffix . $path;
    }

    /**
     * Show the login selector page.
     */
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Show the client login form.
     */
    public function showClient(): Response
    {
        return Inertia::render('Auth/LoginClient');
    }

    /**
     * Show the provider login form.
     */
    public function showProvider(): Response
    {
        return Inertia::render('Auth/LoginProvider');
    }

    /**
     * Handle unified login request.
     */
    public function store(LoginRequest $request): SymfonyResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        return $this->redirectForRole($request, $user->role);
    }

    /**
     * Handle client login request.
     */
    public function storeClient(LoginRequest $request): RedirectResponse
    {
        $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        return redirect()->intended(route('client.dashboard'));
    }

    /**
     * Handle provider login request.
     * Uses Inertia::location() for cross-domain redirect.
     */
    public function storeProvider(LoginRequest $request): SymfonyResponse
    {
        $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        $url = $this->buildDomainUrl($request, config('app.console_domain'));

        // Use Inertia::location() for cross-domain redirect (forces full page reload)
        return Inertia::location($url);
    }

    /**
     * Handle logout request.
     * Uses Inertia::location() for cross-domain redirect to main site.
     */
    public function destroy(Request $request): SymfonyResponse
    {
        $this->logoutAction->execute();

        $url = $this->buildDomainUrl($request, config('app.domain'));

        return Inertia::location($url);
    }

    /**
     * Get the redirect response for the given role.
     * Uses Inertia::location() for cross-domain redirects.
     */
    private function redirectForRole(Request $request, UserRole $role): SymfonyResponse
    {
        return match ($role) {
            UserRole::Admin => Inertia::location(
                $this->buildDomainUrl($request, config('app.admin_domain'))
            ),
            UserRole::Provider => Inertia::location(
                $this->buildDomainUrl($request, config('app.console_domain'))
            ),
            default => redirect()->intended(route('client.dashboard')),
        };
    }
}
