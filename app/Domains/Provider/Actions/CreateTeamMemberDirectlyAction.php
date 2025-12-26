<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\User\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTeamMemberDirectlyAction
{
    /**
     * Create a team member directly with a new user account.
     *
     * @param  array{email: string, name: string, permissions: array<string>, send_credentials?: bool}  $data
     * @return array{team_member: TeamMember, user: User, temporary_password: string|null}
     */
    public function execute(Provider $provider, array $data): array
    {
        // Validate permissions
        $permissions = TeamPermission::validate($data['permissions'] ?? TeamPermission::defaults());

        // Check if user already exists
        $existingUser = User::where('email', $data['email'])->first();

        if ($existingUser) {
            // Link existing user to team
            $teamMember = $provider->teamMembers()->create([
                'email' => $data['email'],
                'name' => $existingUser->name,
                'user_id' => $existingUser->id,
                'permissions' => $permissions,
                'status' => TeamMemberStatus::ACTIVE,
                'accepted_at' => now(),
            ]);

            return [
                'team_member' => $teamMember,
                'user' => $existingUser,
                'temporary_password' => null,
            ];
        }

        // Create new user account
        $temporaryPassword = Str::random(12);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => UserRole::Provider, // Team members need provider role to access console
            'is_active' => true,
        ]);

        // Create team member linked to new user
        $teamMember = $provider->teamMembers()->create([
            'email' => $data['email'],
            'name' => $data['name'],
            'user_id' => $user->id,
            'permissions' => $permissions,
            'status' => TeamMemberStatus::ACTIVE,
            'accepted_at' => now(),
        ]);

        // Optionally send credentials email
        if ($data['send_credentials'] ?? false) {
            // TODO: Send credentials email with temporary password
            // Mail::to($user->email)->queue(new TeamMemberCredentialsMail($user, $provider, $temporaryPassword));
        }

        return [
            'team_member' => $teamMember,
            'user' => $user,
            'temporary_password' => $temporaryPassword,
        ];
    }
}
