<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Provider\Models\TeamMember;

class UpdateTeamMemberPermissionsAction
{
    /**
     * Update a team member's permissions.
     *
     * @param  array{permissions: array<string>}  $data
     */
    public function execute(TeamMember $teamMember, array $data): TeamMember
    {
        // Validate and set permissions
        $permissions = TeamPermission::validate($data['permissions'] ?? []);
        $teamMember->setPermissions($permissions);

        return $teamMember;
    }

    /**
     * Apply a preset to a team member.
     */
    public function applyPreset(TeamMember $teamMember, string $preset): TeamMember
    {
        $presets = TeamPermission::presets();

        if (! isset($presets[$preset])) {
            throw new \InvalidArgumentException("Unknown preset: {$preset}");
        }

        $teamMember->setPermissions($presets[$preset]['permissions']);

        return $teamMember;
    }
}
