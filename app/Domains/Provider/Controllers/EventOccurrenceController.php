<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventOccurrence;
use App\Domains\Event\Resources\EventOccurrenceResource;
use App\Domains\Event\Services\RecurrenceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventOccurrenceController extends Controller
{
    public function __construct(
        private RecurrenceService $recurrenceService
    ) {}

    /**
     * List all occurrences for an event.
     */
    public function index(Event $event): JsonResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        $occurrences = $event->occurrences()
            ->with('bookings')
            ->orderBy('start_datetime')
            ->get();

        return response()->json([
            'occurrences' => $occurrences->map(fn ($o) => (new EventOccurrenceResource($o))
                ->withBookings()
                ->resolve()),
        ]);
    }

    /**
     * Generate more occurrences for a recurring event.
     */
    public function generate(Request $request, Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        // Only recurring events can generate occurrences
        if (! $event->isRecurring()) {
            return back()->with('error', 'Only recurring events can generate occurrences.');
        }

        $months = $request->input('months', 3);
        $occurrences = $this->recurrenceService->generateOccurrences($event, $months);

        $count = $occurrences->count();
        $message = $count > 0
            ? "Generated {$count} new occurrence(s)."
            : 'No new occurrences to generate.';

        return back()->with('success', $message);
    }

    /**
     * Cancel a specific occurrence.
     */
    public function cancel(Request $request, EventOccurrence $occurrence): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Load the event relationship
        $occurrence->load('event');

        // Ensure the occurrence's event belongs to this provider
        if ($occurrence->event->provider_id !== $provider->id) {
            abort(403);
        }

        // Check if occurrence can be cancelled
        if (! $occurrence->canBeCancelled()) {
            return back()->with('error', 'This occurrence cannot be cancelled.');
        }

        $reason = $request->input('reason', 'Cancelled by provider');

        $occurrence->update([
            'status' => OccurrenceStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);

        // TODO: Notify booked attendees about the cancellation

        return back()->with('success', 'Occurrence cancelled successfully.');
    }

    /**
     * Mark an occurrence as completed.
     */
    public function complete(EventOccurrence $occurrence): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Load the event relationship
        $occurrence->load('event');

        // Ensure the occurrence's event belongs to this provider
        if ($occurrence->event->provider_id !== $provider->id) {
            abort(403);
        }

        // Only scheduled occurrences can be marked as completed
        if (! $occurrence->isScheduled()) {
            return back()->with('error', 'Only scheduled occurrences can be marked as completed.');
        }

        $occurrence->update([
            'status' => OccurrenceStatus::COMPLETED,
        ]);

        return back()->with('success', 'Occurrence marked as completed.');
    }

    /**
     * Update capacity override for an occurrence.
     */
    public function updateCapacity(Request $request, EventOccurrence $occurrence): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Load the event relationship
        $occurrence->load('event');

        // Ensure the occurrence's event belongs to this provider
        if ($occurrence->event->provider_id !== $provider->id) {
            abort(403);
        }

        $validated = $request->validate([
            'capacity_override' => ['nullable', 'integer', 'min:1', 'max:10000'],
        ]);

        $occurrence->update([
            'capacity_override' => $validated['capacity_override'],
        ]);

        return back()->with('success', 'Occurrence capacity updated.');
    }
}
