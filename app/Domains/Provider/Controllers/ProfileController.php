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

        // Load relationships for availability
        $teamMember->load(['availability', 'breaks', 'blockedDates']);

        // Get provider availability as defaults
        $providerAvailability = $provider->availability()
            ->orderBy('day_of_week')
            ->get()
            ->map(fn ($a) => [
                'day_of_week' => $a->day_of_week,
                'is_available' => $a->is_available,
                'start_time' => $a->start_time,
                'end_time' => $a->end_time,
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
            'availability' => [
                'schedule' => $teamMember->availability->map(fn ($a) => [
                    'id' => $a->id,
                    'day_of_week' => $a->day_of_week,
                    'is_available' => $a->is_available,
                    'start_time' => $a->start_time,
                    'end_time' => $a->end_time,
                    'use_provider_defaults' => $a->use_provider_defaults,
                ]),
                'breaks' => $teamMember->breaks->map(fn ($b) => [
                    'id' => $b->id,
                    'uuid' => $b->uuid,
                    'name' => $b->name,
                    'day_of_week' => $b->day_of_week,
                    'start_time' => $b->start_time,
                    'end_time' => $b->end_time,
                ]),
                'blockedDates' => $teamMember->blockedDates->map(fn ($bd) => [
                    'id' => $bd->id,
                    'uuid' => $bd->uuid,
                    'date' => $bd->date->format('Y-m-d'),
                    'reason' => $bd->reason,
                    'is_recurring' => $bd->is_recurring ?? false,
                ]),
            ],
            'providerDefaults' => [
                'availability' => $providerAvailability,
            ],
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
