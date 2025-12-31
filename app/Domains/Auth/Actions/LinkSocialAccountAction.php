<?php

namespace App\Domains\Auth\Actions;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class LinkSocialAccountAction
{
    /**
     * Link a social account to an existing user.
     */
    public function execute(User $user, string $provider, SocialiteUser $socialUser): User
    {
        $socialIdColumn = "{$provider}_id";
        $linkedAtColumn = "{$provider}_linked_at";

        // Check if this social account is already linked to another user
        $existingUser = User::where($socialIdColumn, $socialUser->getId())
            ->where('id', '!=', $user->id)
            ->first();

        if ($existingUser) {
            throw ValidationException::withMessages([
                'social' => "This {$provider} account is already linked to another user.",
            ]);
        }

        // Check if user already has this provider linked
        if ($user->hasSocialAccountLinked($provider)) {
            throw ValidationException::withMessages([
                'social' => "You already have a {$provider} account linked.",
            ]);
        }

        // Link the account
        $user->update([
            $socialIdColumn => $socialUser->getId(),
            $linkedAtColumn => now(),
        ]);

        return $user;
    }
}
