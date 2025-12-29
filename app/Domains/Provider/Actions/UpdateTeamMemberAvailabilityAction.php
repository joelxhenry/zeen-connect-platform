<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\TeamMember;
use App\Domains\Provider\Models\TeamMemberAvailability;
use Illuminate\Support\Facades\DB;

class UpdateTeamMemberAvailabilityAction
{
    /**
     * Update a team member's weekly availability schedule.
     *
     * @param  array<int, array{day_of_week: int, is_available: bool, start_time: ?string, end_time: ?string, use_provider_defaults: bool}>  $schedule
     */
    public function execute(TeamMember $teamMember, array $schedule): void
    {
        DB::transaction(function () use ($teamMember, $schedule) {
            foreach ($schedule as $dayData) {
                $this->updateDayAvailability($teamMember, $dayData);
            }
        });
    }

    /**
     * Update availability for a single day.
     */
    protected function updateDayAvailability(TeamMember $teamMember, array $dayData): void
    {
        $dayOfWeek = $dayData['day_of_week'];

        // Validate day of week
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            return;
        }

        // If using provider defaults, we can either delete the record or update it
        if ($dayData['use_provider_defaults'] ?? true) {
            TeamMemberAvailability::updateOrCreate(
                [
                    'team_member_id' => $teamMember->id,
                    'day_of_week' => $dayOfWeek,
                ],
                [
                    'use_provider_defaults' => true,
                    'is_available' => true,
                    'start_time' => null,
                    'end_time' => null,
                ]
            );

            return;
        }

        // Custom schedule
        TeamMemberAvailability::updateOrCreate(
            [
                'team_member_id' => $teamMember->id,
                'day_of_week' => $dayOfWeek,
            ],
            [
                'use_provider_defaults' => false,
                'is_available' => $dayData['is_available'] ?? false,
                'start_time' => $dayData['start_time'] ?? null,
                'end_time' => $dayData['end_time'] ?? null,
            ]
        );
    }

    /**
     * Reset a team member's schedule to use provider defaults for all days.
     */
    public function resetToProviderDefaults(TeamMember $teamMember): void
    {
        $teamMember->availability()->update([
            'use_provider_defaults' => true,
            'is_available' => true,
            'start_time' => null,
            'end_time' => null,
        ]);
    }

    /**
     * Copy provider schedule to team member as custom overrides.
     */
    public function copyFromProvider(TeamMember $teamMember): void
    {
        $providerAvailability = $teamMember->provider->availability()->get();

        DB::transaction(function () use ($teamMember, $providerAvailability) {
            foreach ($providerAvailability as $availability) {
                TeamMemberAvailability::updateOrCreate(
                    [
                        'team_member_id' => $teamMember->id,
                        'day_of_week' => $availability->day_of_week,
                    ],
                    [
                        'use_provider_defaults' => false,
                        'is_available' => $availability->is_available,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ]
                );
            }
        });
    }
}
