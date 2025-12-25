<?php

namespace App\Domains\Admin\Controllers\Auth;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AdminLoginController extends Controller
{
    public function __construct(
        private LoginUserAction $loginAction,
        private LogoutUserAction $logoutAction,
    ) {}

    /**
     * Show the admin login form.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login');
    }

    /**
     * Handle admin login request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $this->loginAction->execute(
            $request->only('email', 'password'),
            $request->boolean('remember'),
            UserRole::Admin
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Handle admin logout request.
     */
    public function destroy(): RedirectResponse
    {
        $this->logoutAction->execute();

        return redirect()->route('admin.login');
    }
}
