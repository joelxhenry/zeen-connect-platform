<?php

namespace App\Domains\Auth\Actions;

use App\Domains\Client\Models\Client;
use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthAction
{
    /**
     * Find or create a user from a social login.
     * New users default to CLIENT role.
     */
    public function execute(string $provider, SocialiteUser $socialUser): User
    {
        return DB::transaction(function () use ($provider, $socialUser) {
            // First, check if user exists with this social ID
            $user = $this->findBySocialId($provider, $socialUser->getId());

            if ($user) {
                return $this->updateSocialUser($user, $socialUser);
            }

            // No existing social link - create new user
            // Note: We do NOT auto-link by email per requirements
            return $this->createNewSocialUser($provider, $socialUser);
        });
    }

    /**
     * Find user by social provider ID.
     */
    private function findBySocialId(string $provider, string $socialId): ?User
    {
        $column = "{$provider}_id";

        return User::where($column, $socialId)->first();
    }

    /**
     * Update existing social user's info (avatar if not set).
     */
    private function updateSocialUser(User $user, SocialiteUser $socialUser): User
    {
        // Optionally update avatar from social provider if user doesn't have one
        if ($socialUser->getAvatar() && ! $user->avatar) {
            $user->avatar = $socialUser->getAvatar();
            $user->save();
        }

        return $user;
    }

    /**
     * Create a new user from social login.
     * Defaults to CLIENT role per requirements.
     */
    private function createNewSocialUser(string $provider, SocialiteUser $socialUser): User
    {
        $socialIdColumn = "{$provider}_id";
        $linkedAtColumn = "{$provider}_linked_at";

        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => null, // Social-only user, no password
            'avatar' => $socialUser->getAvatar(),
            'role' => UserRole::Client, // Default to client role
            'is_active' => true,
            'email_verified_at' => now(), // Social emails are verified by provider
            $socialIdColumn => $socialUser->getId(),
            $linkedAtColumn => now(),
        ]);

        // Create client profile for new social users
        Client::create([
            'user_id' => $user->id,
        ]);

        return $user;
    }
}
