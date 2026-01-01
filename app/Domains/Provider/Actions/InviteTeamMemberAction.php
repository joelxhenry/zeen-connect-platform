<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Provider\Mail\TeamInvitationMail;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use Illuminate\Support\Facades\Mail;

class InviteTeamMemberAction
{
    /**
     * Invite a new team member via email.
     *
     * @param  array{email: string, name?: string, title?: string, permissions: array<string>}  $data
     */
    public function execute(Provider $provider, array $data): TeamMember
    {
        // Validate permissions
        $permissions = TeamPermission::validate($data['permissions'] ?? TeamPermission::defaults());

        // Create the team member record
        $teamMember = $provider->teamMembers()->create([
            'email' => $data['email'],
            'name' => $data['name'] ?? null,
            'title' => $data['title'] ?? null,
            'permissions' => $permissions,
            'status' => TeamMemberStatus::PENDING,
        ]);

        // Generate invitation token
        $teamMember->generateInvitationToken();

        // Send invitation email
        Mail::to($data['email'])->queue(new TeamInvitationMail($teamMember));

        return $teamMember;
    }
}
