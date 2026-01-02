<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Models\TeamMember;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user/team member profile edit form.
     */
    public function edit(): Response
    {
        $user = Auth::user();
        $provider = $user->provider;

        // Get the team member record for this user-provider combination
        // If user is owner, create a team member record if needed
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        // Get provider-level weekly availability
        $availability = $provider->availability()
            ->orderBy('day_of_week')
            ->get()
            ->keyBy('day_of_week');

        // Ensure all days have an entry for the UI
        $weeklySchedule = collect(range(0, 6))->map(function ($day) use ($availability) {
            if ($availability->has($day)) {
                $slot = $availability->get($day);

                return [
                    'day_of_week' => $day,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'is_available' => $slot->is_available,
                ];
            }

            // Default values for days without schedule
            return [
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'is_available' => false,
            ];
        })->values();

        // Get blocked dates (future only)
        $blockedDates = $provider->blockedDates()
            ->future()
            ->orderBy('date')
            ->get()
            ->map(fn ($blocked) => [
                'id' => $blocked->id,
                'date' => $blocked->date->format('Y-m-d'),
                'reason' => $blocked->reason,
            ]);

        // Get breaks organized by day
        $breaks = $provider->breaks()
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

        return Inertia::render('Provider/Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar_url' => $user->avatar_url,
                'avatar_media' => $user->getFirstMedia('avatar')?->toMediaArray(),
            ],
            'teamMember' => [
                'id' => $teamMember->id,
                'uuid' => $teamMember->uuid,
                'title' => $teamMember->title,
            ],
            'isOwner' => $provider->user_id === $user->id,
            'weeklySchedule' => $weeklySchedule,
            'blockedDates' => $blockedDates,
            'breaks' => $breaks,
        ]);
    }

    /**
     * Update the user's personal information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'title' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($user, $teamMember, $validated) {
            // Update user record
            $user->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ]);

            // Update team member title
            if (isset($validated['title'])) {
                $teamMember->update(['title' => $validated['title']]);
            }
        });

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update the user's availability schedule.
     */
    public function updateAvailability(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'schedule' => ['required', 'array'],
            'schedule.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'schedule.*.is_available' => ['required', 'boolean'],
            'schedule.*.start_time' => ['nullable', 'date_format:H:i'],
            'schedule.*.end_time' => ['nullable', 'date_format:H:i', 'after:schedule.*.start_time'],
            'schedule.*.use_provider_defaults' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($teamMember, $validated) {
            foreach ($validated['schedule'] as $day) {
                $teamMember->availability()->updateOrCreate(
                    ['day_of_week' => $day['day_of_week']],
                    [
                        'is_available' => $day['is_available'],
                        'start_time' => $day['start_time'] ?? null,
                        'end_time' => $day['end_time'] ?? null,
                        'use_provider_defaults' => $day['use_provider_defaults'] ?? false,
                    ]
                );
            }
        });

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Availability updated successfully.');
    }

    /**
     * Add a break time.
     */
    public function addBreak(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $teamMember->breaks()->create($validated);

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Break added successfully.');
    }

    /**
     * Remove a break time.
     */
    public function removeBreak(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'break_id' => ['required', 'integer'],
        ]);

        $teamMember->breaks()->where('id', $validated['break_id'])->delete();

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Break removed successfully.');
    }

    /**
     * Block a date (time off).
     */
    public function blockDate(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $teamMember->blockedDates()->create([
            'date' => $validated['date'],
            'reason' => $validated['reason'] ?? null,
        ]);

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Time off blocked successfully.');
    }

    /**
     * Unblock a date.
     */
    public function unblockDate(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $provider = $user->provider;
        $teamMember = $this->getOrCreateTeamMember($user, $provider);

        $validated = $request->validate([
            'blocked_date_id' => ['required', 'integer'],
        ]);

        $teamMember->blockedDates()->where('id', $validated['blocked_date_id'])->delete();

        return redirect()
            ->route('provider.profile.edit')
            ->with('success', 'Time off removed successfully.');
    }

    /**
     * Get or create a team member record for the user-provider combination.
     */
    private function getOrCreateTeamMember($user, $provider): TeamMember
    {
        // Check if user already has a team member record for this provider
        $teamMember = TeamMember::where('user_id', $user->id)
            ->where('provider_id', $provider->id)
            ->first();

        if (! $teamMember) {
            // Create a team member record for the owner
            $teamMember = TeamMember::create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'title' => 'Owner',
                'permissions' => [], // Owners have full access via ownership
                'status' => \App\Domains\Provider\Enums\TeamMemberStatus::ACTIVE,
                'accepted_at' => now(),
            ]);
        }

        return $teamMember;
    }
}
