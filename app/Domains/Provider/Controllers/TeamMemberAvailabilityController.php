<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\UpdateAvailabilityBreaksAction;
use App\Domains\Provider\Actions\UpdateTeamMemberAvailabilityAction;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Provider\Services\TeamMemberAvailabilityService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeamMemberAvailabilityController extends Controller
{
    public function __construct(
        protected TeamMemberAvailabilityService $availabilityService
    ) {}

    /**
     * Show the team member availability edit form.
     */
    public function edit(Request $request, TeamMember $member): Response
    {
        $provider = $request->user()->provider;

        // Ensure the team member belongs to the provider
        abort_unless($member->provider_id === $provider->id, 403);

        // Get provider's weekly schedule for inheritance display
        $providerSchedule = $provider->availability()
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week')
            ->map(fn ($slot) => [
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'is_available' => $slot->is_available,
            ]);

        // Get team member's weekly schedule with inheritance
        $weeklySchedule = $this->availabilityService->getWeeklySchedule($member);

        // Get blocked dates (combined provider + team member)
        $blockedDates = $this->availabilityService->getBlockedDates(
            $member,
            now()->format('Y-m-d'),
            now()->addYear()->format('Y-m-d')
        );

        // Get team member's own blocked dates for editing
        $teamMemberBlockedDates = $member->blockedDates()
            ->future()
            ->orderBy('date')
            ->get()
            ->map(fn ($blocked) => [
                'id' => $blocked->id,
                'date' => $blocked->date->format('Y-m-d'),
                'reason' => $blocked->reason,
            ]);

        // Get team member breaks
        $breaks = $member->breaks()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->map(fn ($break) => [
                'id' => $break->id,
                'day_of_week' => $break->day_of_week,
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
                'label' => $break->label,
            ]);

        return Inertia::render('Provider/Team/Availability', [
            'teamMember' => [
                'id' => $member->id,
                'uuid' => $member->uuid,
                'name' => $member->display_name,
                'email' => $member->email,
            ],
            'providerSchedule' => $providerSchedule,
            'weeklySchedule' => $weeklySchedule,
            'blockedDates' => $blockedDates,
            'teamMemberBlockedDates' => $teamMemberBlockedDates,
            'breaks' => $breaks,
        ]);
    }

    /**
     * Update team member's weekly schedule.
     */
    public function updateSchedule(
        Request $request,
        TeamMember $member,
        UpdateTeamMemberAvailabilityAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;
        abort_unless($member->provider_id === $provider->id, 403);

        $validated = $request->validate([
            'schedule' => 'required|array',
            'schedule.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedule.*.use_provider_defaults' => 'required|boolean',
            'schedule.*.is_available' => 'required_if:schedule.*.use_provider_defaults,false|boolean',
            'schedule.*.start_time' => 'required_if:schedule.*.use_provider_defaults,false|nullable|date_format:H:i',
            'schedule.*.end_time' => 'required_if:schedule.*.use_provider_defaults,false|nullable|date_format:H:i|after:schedule.*.start_time',
        ]);

        $action->execute($member, $validated['schedule']);

        return back()->with('success', 'Schedule updated successfully.');
    }

    /**
     * Update team member's breaks.
     */
    public function updateBreaks(
        Request $request,
        TeamMember $member,
        UpdateAvailabilityBreaksAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;
        abort_unless($member->provider_id === $provider->id, 403);

        $validated = $request->validate([
            'breaks' => 'array',
            'breaks.*.day_of_week' => 'required|integer|min:0|max:6',
            'breaks.*.start_time' => 'required|date_format:H:i',
            'breaks.*.end_time' => 'required|date_format:H:i|after:breaks.*.start_time',
            'breaks.*.label' => 'nullable|string|max:50',
        ]);

        $action->execute($member, $validated['breaks'] ?? []);

        return back()->with('success', 'Breaks updated successfully.');
    }

    /**
     * Update team member's blocked dates.
     */
    public function updateBlockedDates(Request $request, TeamMember $member): RedirectResponse
    {
        $provider = $request->user()->provider;
        abort_unless($member->provider_id === $provider->id, 403);

        $validated = $request->validate([
            'blocked_dates' => 'array',
            'blocked_dates.*.date' => 'required|date|after_or_equal:today',
            'blocked_dates.*.reason' => 'nullable|string|max:255',
        ]);

        // Sync blocked dates
        $member->blockedDates()->delete();

        foreach ($validated['blocked_dates'] ?? [] as $dateData) {
            $member->blockedDates()->create([
                'date' => $dateData['date'],
                'reason' => $dateData['reason'] ?? null,
            ]);
        }

        return back()->with('success', 'Blocked dates updated successfully.');
    }

    /**
     * Reset team member's schedule to use provider defaults.
     */
    public function resetToDefaults(
        Request $request,
        TeamMember $member,
        UpdateTeamMemberAvailabilityAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;
        abort_unless($member->provider_id === $provider->id, 403);

        $action->resetToProviderDefaults($member);

        return back()->with('success', 'Schedule reset to provider defaults.');
    }
}
