<?php

namespace App\Domains\Provider\Services;

use App\Domains\Provider\Models\AvailabilityBreak;
use App\Domains\Provider\Models\TeamMember;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TeamMemberAvailabilityService
{
    /**
     * Get the effective schedule for a team member on a specific date.
     * Returns provider defaults if the team member uses them.
     *
     * @return array{is_available: bool, start_time: ?string, end_time: ?string}|null
     */
    public function getScheduleForDate(TeamMember $teamMember, \DateTimeInterface $date): ?array
    {
        $dayOfWeek = (int) $date->format('w');

        return $teamMember->getEffectiveAvailabilityForDay($dayOfWeek);
    }

    /**
     * Get all breaks for a team member on a specific day.
     * Combines team member's own breaks with provider breaks.
     *
     * @return Collection<AvailabilityBreak>
     */
    public function getBreaksForDay(TeamMember $teamMember, int $dayOfWeek): Collection
    {
        return $teamMember->getBreaksForDay($dayOfWeek);
    }

    /**
     * Check if a team member is blocked on a specific date.
     * Checks both team member blocked dates and provider blocked dates.
     */
    public function isBlockedOnDate(TeamMember $teamMember, \DateTimeInterface $date): bool
    {
        $dateString = $date instanceof Carbon ? $date->format('Y-m-d') : $date->format('Y-m-d');

        // Check provider blocked dates first
        if ($teamMember->provider->blockedDates()->where('date', $dateString)->exists()) {
            return true;
        }

        // Check team member blocked dates
        return $teamMember->blockedDates()->where('date', $dateString)->exists();
    }

    /**
     * Get available dates for a team member within a date range.
     */
    public function getAvailableDates(TeamMember $teamMember, string $startDate, string $endDate): array
    {
        $availableDates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current->lte($end)) {
            if ($this->isAvailableOnDate($teamMember, $current)) {
                $availableDates[] = $current->format('Y-m-d');
            }

            $current->addDay();
        }

        return $availableDates;
    }

    /**
     * Check if a team member is available on a specific date.
     */
    public function isAvailableOnDate(TeamMember $teamMember, \DateTimeInterface $date): bool
    {
        // Check if blocked
        if ($this->isBlockedOnDate($teamMember, $date)) {
            return false;
        }

        // Check availability schedule
        $schedule = $this->getScheduleForDate($teamMember, $date);

        return $schedule && $schedule['is_available'];
    }

    /**
     * Get the weekly schedule for a team member.
     * Includes both custom overrides and inherited provider defaults.
     */
    public function getWeeklySchedule(TeamMember $teamMember): array
    {
        $schedule = [];
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        for ($dayOfWeek = 0; $dayOfWeek <= 6; $dayOfWeek++) {
            // Get the team member's availability record for this day
            $customAvailability = $teamMember->availability()
                ->where('day_of_week', $dayOfWeek)
                ->first();

            // Get the effective availability
            $effectiveSchedule = $teamMember->getEffectiveAvailabilityForDay($dayOfWeek);

            $schedule[$dayOfWeek] = [
                'day_of_week' => $dayOfWeek,
                'day_name' => $days[$dayOfWeek],
                'use_provider_defaults' => $customAvailability?->use_provider_defaults ?? true,
                'is_available' => $effectiveSchedule['is_available'] ?? false,
                'start_time' => $effectiveSchedule['start_time'] ?? null,
                'end_time' => $effectiveSchedule['end_time'] ?? null,
                'breaks' => $this->getBreaksForDay($teamMember, $dayOfWeek)->values()->toArray(),
            ];
        }

        return $schedule;
    }

    /**
     * Get blocked dates for a team member in a date range.
     * Combines both team member and provider blocked dates.
     */
    public function getBlockedDates(TeamMember $teamMember, string $startDate, string $endDate): array
    {
        // Get provider blocked dates
        $providerBlockedDates = $teamMember->provider->blockedDates()
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn ($b) => [
                'date' => $b->date->format('Y-m-d'),
                'reason' => $b->reason,
                'source' => 'provider',
            ]);

        // Get team member blocked dates
        $teamBlockedDates = $teamMember->blockedDates()
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->map(fn ($b) => [
                'date' => $b->date->format('Y-m-d'),
                'reason' => $b->reason,
                'source' => 'team_member',
            ]);

        return $providerBlockedDates->merge($teamBlockedDates)
            ->sortBy('date')
            ->values()
            ->toArray();
    }
}
