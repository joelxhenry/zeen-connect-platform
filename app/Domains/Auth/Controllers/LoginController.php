<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LinkSocialAccountAction;
use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Actions\SocialAuthAction;
use App\Domains\Auth\Enums\SocialProvider;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class LoginController extends Controller
{
    public function __construct(
        private LoginUserAction $loginAction,
        private LogoutUserAction $logoutAction,
        private SocialAuthAction $socialAuthAction,
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



    /**
     * Redirect to social provider for authentication.
     */
    public function redirectToProvider(Request $request, string $provider): SymfonyResponse
    {
        // Validate provider
        if (! SocialProvider::isValid($provider)) {
            abort(404, 'Invalid social provider');
        }

        // Store intended URL and context for after callback
        session()->put('social_auth_intended', $request->query('intended', route('client.dashboard')));
        session()->put('social_auth_context', $request->query('context', 'login')); // 'login' or 'link'

        // Get the Socialite driver
        $driver = Socialite::driver($provider);

        // Apple requires specific scopes
        if ($provider === 'apple') {
            $driver->scopes(['name', 'email']);
        }

        return $driver->redirect();
    }

    /**
     * Handle callback from social provider.
     */
    public function handleProviderCallback(Request $request, string $provider): SymfonyResponse
    {
        // Validate provider
        if (! SocialProvider::isValid($provider)) {
            abort(404, 'Invalid social provider');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Failed to authenticate with ' . ucfirst($provider) . '. Please try again.');
        }

        // Check if email is provided (Apple can hide email)
        if (! $socialUser->getEmail()) {
            return redirect()->route('login')
                ->with('error', 'Email is required for registration. Please allow email access.');
        }

        // Get context from session
        $context = session()->pull('social_auth_context', 'login');
        $intended = session()->pull('social_auth_intended', route('client.dashboard'));

        // If this is a link request and user is authenticated
        if ($context === 'link' && Auth::check()) {
            return $this->handleLinkCallback($request, $provider, $socialUser);
        }

        // Execute social auth action
        $user = $this->socialAuthAction->execute($provider, $socialUser);

        // Check if user is active
        if (! $user->is_active) {
            return redirect()->route('login')
                ->with('error', 'Your account has been deactivated.');
        }

        // Log the user in
        Auth::login($user, true);
        session()->regenerate();
        $user->updateLastLogin();

        // Redirect based on user role
        return $this->redirectForRole($request, $user->role);
    }

    /**
     * Handle linking a social account to existing user.
     */
    private function handleLinkCallback(Request $request, string $provider, $socialUser): SymfonyResponse
    {
        $linkAction = app(LinkSocialAccountAction::class);

        try {
            $linkAction->execute(Auth::user(), $provider, $socialUser);

            return redirect()->route('client.profile.edit')
                ->with('success', ucfirst($provider) . ' account linked successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('client.profile.edit')
                ->withErrors($e->errors());
        }
    }
}
