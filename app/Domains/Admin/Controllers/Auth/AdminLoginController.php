<?php

namespace App\Domains\Admin\Controllers\Auth;

use App\Domains\Auth\Actions\LoginUserAction;
use App\Domains\Auth\Actions\LogoutUserAction;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\User\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminLoginController extends Controller
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
            $request->boolean('remember')
        );

        // Verify user has admin role
        if ($user->role !== UserRole::Admin) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('You do not have admin access.'),
            ]);
        }

        $dashboardUrl = $this->buildDomainUrl($request, config('app.admin_domain'));

        return redirect()->intended($dashboardUrl);
    }

    /**
     * Handle admin logout request.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $this->logoutAction->execute();

        $loginUrl = $this->buildDomainUrl($request, config('app.admin_domain'), '/login');

        return redirect()->to($loginUrl);
    }
}
