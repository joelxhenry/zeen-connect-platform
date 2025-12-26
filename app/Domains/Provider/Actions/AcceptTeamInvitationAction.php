<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\TeamMember;
use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AcceptTeamInvitationAction
{
    /**
     * Accept a team invitation and link/create user account.
     *
     * @param  array{name?: string, password?: string}|null  $userData  For creating new user
     * @return array{team_member: TeamMember, user: User}
     */
    public function execute(TeamMember $teamMember, ?User $existingUser = null, ?array $userData = null): array
    {
        // If invitation has expired, throw exception
        if ($teamMember->isInvitationExpired()) {
            throw new \Exception('This invitation has expired.');
        }

        // If already accepted, throw exception
        if (! $teamMember->isPending()) {
            throw new \Exception('This invitation has already been processed.');
        }

        $user = $existingUser;

        // If no existing user, create one from the provided data
        if (! $user && $userData) {
            $user = User::create([
                'name' => $userData['name'] ?? $teamMember->name ?? explode('@', $teamMember->email)[0],
                'email' => $teamMember->email,
                'password' => Hash::make($userData['password']),
                'role' => UserRole::Provider, // Team members need provider role
                'is_active' => true,
            ]);
        }

        if (! $user) {
            throw new \Exception('No user account provided or created.');
        }

        // Verify the user email matches the invitation
        if ($user->email !== $teamMember->email) {
            throw new \Exception('User email does not match invitation.');
        }

        // Accept the invitation
        $teamMember->acceptInvitation($user);

        return [
            'team_member' => $teamMember,
            'user' => $user,
        ];
    }

    /**
     * Find a team member by invitation token.
     */
    public function findByToken(string $token): ?TeamMember
    {
        return TeamMember::byToken($token)->first();
    }
}
