<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\UpdateAvailabilityAction;
use App\Domains\Provider\Actions\UpdateAvailabilityBreaksAction;
use App\Domains\Provider\Actions\UpdateBlockedDatesAction;
use App\Domains\Provider\Requests\UpdateAvailabilityRequest;
use App\Domains\Provider\Requests\UpdateBlockedDatesRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    /**
     * Show the availability edit form.
     */
    public function edit(Request $request): Response
    {
        $provider = $request->user()->provider;

        // Get weekly availability (create defaults if none exist)
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

        return Inertia::render('Provider/Availability/Edit', [
            'weeklySchedule' => $weeklySchedule,
            'blockedDates' => $blockedDates,
            'breaks' => $breaks,
            'bufferMinutes' => $provider->buffer_minutes ?? 0,
        ]);
    }

    /**
     * Update the weekly availability schedule.
     */
    public function updateSchedule(
        UpdateAvailabilityRequest $request,
        UpdateAvailabilityAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;

        $action->execute($provider, $request->validated('schedule'));

        return back()->with('success', 'Weekly schedule updated successfully.');
    }

    /**
     * Update blocked dates.
     */
    public function updateBlockedDates(
        UpdateBlockedDatesRequest $request,
        UpdateBlockedDatesAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;

        $action->execute($provider, $request->validated('blocked_dates'));

        return back()->with('success', 'Blocked dates updated successfully.');
    }

    /**
     * Update availability breaks.
     */
    public function updateBreaks(
        Request $request,
        UpdateAvailabilityBreaksAction $action
    ): RedirectResponse {
        $provider = $request->user()->provider;

        $validated = $request->validate([
            'breaks' => 'array',
            'breaks.*.day_of_week' => 'required|integer|min:0|max:6',
            'breaks.*.start_time' => 'required|date_format:H:i',
            'breaks.*.end_time' => 'required|date_format:H:i|after:breaks.*.start_time',
            'breaks.*.label' => 'nullable|string|max:50',
        ]);

        $action->execute($provider, $validated['breaks'] ?? []);

        return back()->with('success', 'Breaks updated successfully.');
    }

    /**
     * Update buffer time between bookings.
     */
    public function updateBuffer(Request $request): RedirectResponse
    {
        $provider = $request->user()->provider;

        $validated = $request->validate([
            'buffer_minutes' => 'required|integer|min:0|max:120',
        ]);

        $provider->update([
            'buffer_minutes' => $validated['buffer_minutes'],
        ]);

        return back()->with('success', 'Buffer time updated successfully.');
    }
}
