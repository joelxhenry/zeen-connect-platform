<?php

namespace App\Domains\Auth\Actions;

use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * Execute the login action.
     *
     * @param  array  $credentials  ['email' => string, 'password' => string]
     * @param  bool  $remember  Whether to remember the session
     * @param  UserRole|null  $role  Optional role to filter by (for role-specific logins)
     */
    public function execute(array $credentials, bool $remember = false, ?UserRole $role = null): User
    {
        $query = User::where('email', $credentials['email']);

        if ($role) {
            $query->where('role', $role);
        }

        $user = $query->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => __('Your account has been deactivated.'),
            ]);
        }

        Auth::login($user, $remember);
        session()->regenerate();
        $user->updateLastLogin();

        return $user;
    }

    /**
     * Check if an email has multiple accounts across different roles.
     *
     * @return array{count: int, roles: array<string>}
     */
    public function getAccountsForEmail(string $email): array
    {
        $users = User::where('email', $email)
            ->where('is_active', true)
            ->get(['role']);

        return [
            'count' => $users->count(),
            'roles' => $users->pluck('role')->map(fn ($role) => $role->value)->toArray(),
        ];
    }

    /**
     * Validate password for any account with the given email.
     * Used when multiple accounts exist to verify password before showing role selector.
     */
    public function validatePasswordForEmail(string $email, string $password): bool
    {
        $users = User::where('email', $email)->get();

        foreach ($users as $user) {
            if (Hash::check($password, $user->password)) {
                return true;
            }
        }

        return false;
    }
}
