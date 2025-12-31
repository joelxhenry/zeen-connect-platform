<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UnlinkSocialAccountAction
{
    /**
     * Unlink a social account from a user.
     */
    public function execute(User $user, string $provider): User
    {
        $socialIdColumn = "{$provider}_id";
        $linkedAtColumn = "{$provider}_linked_at";

        // Prevent unlinking if it's the only auth method
        if ($user->isSocialOnlyUser()) {
            $linkedProviders = $user->getLinkedSocialProviders();

            if (count($linkedProviders) <= 1) {
                throw ValidationException::withMessages([
                    'social' => 'You cannot unlink your only authentication method. Please set a password first.',
                ]);
            }
        }

        // Check if this provider is actually linked
        if (! $user->hasSocialAccountLinked($provider)) {
            throw ValidationException::withMessages([
                'social' => "No {$provider} account is linked to your profile.",
            ]);
        }

        // Unlink the account
        $user->update([
            $socialIdColumn => null,
            $linkedAtColumn => null,
        ]);

        return $user;
    }
}
