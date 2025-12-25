<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     * Handle unified login request.
     * If multiple accounts exist for the email, redirect to role selector.
     */
    public function store(LoginRequest $request): RedirectResponse|Response
    {
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Check how many accounts exist for this email
        $accounts = $this->loginAction->getAccountsForEmail($email);

        if ($accounts['count'] === 0) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Single account - proceed with normal login
        if ($accounts['count'] === 1) {
            $user = $this->loginAction->execute(
                $request->only('email', 'password'),
                $remember
            );

            return $this->redirectForRole($user->role);
        }

        // Multiple accounts - validate password first, then show role selector
        if (! $this->loginAction->validatePasswordForEmail($email, $password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Store credentials in session for role selection
        session([
            'pending_login' => [
                'email' => $email,
                'password' => encrypt($password),
                'remember' => $remember,
                'roles' => $accounts['roles'],
            ],
        ]);

        return redirect()->route('login.select-role');
    }

    /**
     * Show the role selector page.
     */
    public function showSelectRole(): Response|RedirectResponse
    {
        $pendingLogin = session('pending_login');

        if (! $pendingLogin) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/SelectRole', [
            'roles' => $pendingLogin['roles'],
        ]);
    }

    /**
     * Complete login after role selection.
     */
    public function storeSelectRole(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'in:client,provider,admin'],
        ]);

        $pendingLogin = session('pending_login');

        if (! $pendingLogin) {
            return redirect()->route('login');
        }

        $role = UserRole::from($request->input('role'));

        // Verify the selected role is one of the available roles
        if (! in_array($role->value, $pendingLogin['roles'])) {
            return redirect()->route('login.select-role')
                ->withErrors(['role' => 'Invalid role selected.']);
        }

        try {
            $user = $this->loginAction->execute(
                [
                    'email' => $pendingLogin['email'],
                    'password' => decrypt($pendingLogin['password']),
                ],
                $pendingLogin['remember'],
                $role
            );

            // Clear pending login from session
            session()->forget('pending_login');

            return $this->redirectForRole($user->role);
        } catch (ValidationException $e) {
            session()->forget('pending_login');

            return redirect()->route('login')->withErrors($e->errors());
        }
    }

    /**
     * Handle client login request.
     */
    public function storeClient(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember'),
            UserRole::Client
        );

        return redirect()->intended(route('client.dashboard'));
    }

    /**
     * Handle provider login request.
     */
    public function storeProvider(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember'),
            UserRole::Provider
        );

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

    /**
     * Get the redirect response for the given role.
     */
    private function redirectForRole(UserRole $role): RedirectResponse
    {
        return match ($role) {
            UserRole::Admin => redirect()->intended(route('admin.dashboard')),
            UserRole::Provider => redirect()->intended(route('provider.dashboard')),
            default => redirect()->intended(route('client.dashboard')),
        };
    }
}
