<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function __construct(
        private LoginUserAction $loginAction,
        private LogoutUserAction $logoutAction,
    ) {}

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
     * Handle unified login request (auto-detects role).
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        return match ($user->role) {
            UserRole::Admin => redirect()->intended(route('admin.dashboard')),
            UserRole::Provider => redirect()->intended(route('provider.dashboard')),
            default => redirect()->intended(route('client.dashboard')),
        };
    }

    /**
     * Handle client login request.
     */
    public function storeClient(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        // Verify user is a client
        if ($user->role !== UserRole::Client) {
            $this->logoutAction->execute();

            throw ValidationException::withMessages([
                'email' => 'This account is not registered as a client. Please use provider login.',
            ]);
        }

        return redirect()->intended(route('client.dashboard'));
    }

    /**
     * Handle provider login request.
     */
    public function storeProvider(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember')
        );

        // Verify user is a provider
        if ($user->role !== UserRole::Provider) {
            $this->logoutAction->execute();

            throw ValidationException::withMessages([
                'email' => 'This account is not registered as a provider. Please use client login.',
            ]);
        }

        return redirect()->intended(route('provider.dashboard'));
    }

    /**
     * Handle logout request.
     */
    public function destroy(): RedirectResponse
    {
        $this->logoutAction->execute();

        return redirect()->route('home');
    }
}
