<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Provider\Actions\UpdateAvailabilityAction;
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

        return Inertia::render('Provider/Availability/Edit', [
            'weeklySchedule' => $weeklySchedule,
            'blockedDates' => $blockedDates,
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
}
