<?php

namespace App\Domains\Provider\Controllers;

use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Resources\EventResource;
use App\Domains\Event\Services\RecurrenceService;
use App\Domains\Provider\Actions\CreateEventAction;
use App\Domains\Provider\Actions\UpdateEventAction;
use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Requests\StoreEventRequest;
use App\Domains\Provider\Requests\UpdateEventRequest;
use App\Domains\Service\Resources\CategoryResource;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(
        private CreateEventAction $createAction,
        private UpdateEventAction $updateAction,
        private RecurrenceService $recurrenceService,
        private SubscriptionService $subscriptionService,
    ) {}

    public function index(): Response
    {
        $provider = Auth::user()->provider;

        $events = $provider->events()
            ->with(['occurrences' => fn ($q) => $q->scheduled()->upcoming()->limit(3)])
            ->withCount(['occurrences', 'bookings'])
            ->ordered()
            ->get();

        // Get event statistics
        $stats = [
            'total' => $events->count(),
            'published' => $events->where('status', EventStatus::PUBLISHED)->count(),
            'draft' => $events->where('status', EventStatus::DRAFT)->count(),
        ];

        // Get provider's event categories for filtering
        $categories = $provider->eventCategories()->active()->ordered()->get();

        return Inertia::render('Provider/Events/Index', [
            'events' => $events->map(fn ($e) => (new EventResource($e))
                ->withOccurrences()
                ->withCounts()
                ->resolve()),
            'stats' => $stats,
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
        ]);
    }

    public function create(): Response
    {
        $provider = Auth::user()->provider;

        // Get provider's event categories
        $categories = $provider->eventCategories()->active()->ordered()->get();

        // Get active team members for assignment
        $teamMembers = $provider->teamMembers()
            ->where('status', TeamMemberStatus::ACTIVE)
            ->get()
            ->map(fn ($tm) => [
                'id' => $tm->id,
                'name' => $tm->display_name,
                'avatar' => $tm->avatar,
            ]);

        return Inertia::render('Provider/Events/Create', [
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
            'providerDefaults' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
            'teamMembers' => $teamMembers,
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $provider = Auth::user()->provider;

        $event = $this->createAction->execute($provider, $request->validated());

        return redirect()
            ->route('provider.events.edit', $event)
            ->with('success', 'Event created successfully.');
    }

    public function edit(Event $event): Response
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        // Load relationships
        $event->load(['recurrenceRule', 'occurrences', 'categories', 'media', 'videoEmbeds', 'teamMembers']);

        // Get provider's event categories
        $categories = $provider->eventCategories()->active()->ordered()->get();

        // Get active team members for assignment
        $teamMembers = $provider->teamMembers()
            ->where('status', TeamMemberStatus::ACTIVE)
            ->get()
            ->map(fn ($tm) => [
                'id' => $tm->id,
                'name' => $tm->display_name,
                'avatar' => $tm->avatar,
            ]);

        return Inertia::render('Provider/Events/Edit', [
            'event' => (new EventResource($event))
                ->withCategories()
                ->withMedia()
                ->withOccurrences()
                ->withRecurrenceRule()
                ->withBookingSettings()
                ->withTeamMembers()
                ->resolve(),
            'categories' => $categories->map(fn ($c) => (new CategoryResource($c))->resolve()),
            'providerDefaults' => $this->subscriptionService->getEffectiveBookingSettings($provider),
            'tierRestrictions' => $this->subscriptionService->getTierRestrictions($provider),
            'providerSubdomain' => $provider->subdomain,
            'teamMembers' => $teamMembers,
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        $this->updateAction->execute($event, $request->validated());

        return back()->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        // Check for active bookings
        if ($event->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return back()->with('error', 'Cannot delete event with active bookings.');
        }

        $event->delete();

        return redirect()
            ->route('provider.events.index')
            ->with('success', 'Event deleted successfully.');
    }

    public function publish(Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        $event->update(['status' => EventStatus::PUBLISHED]);

        // Generate initial occurrences for recurring events
        if ($event->isRecurring()) {
            $this->recurrenceService->generateOccurrences($event);
        }

        return back()->with('success', 'Event published successfully.');
    }

    public function cancel(Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        // Cancel all upcoming occurrences
        $this->recurrenceService->cancelFutureOccurrences($event, 'Event cancelled by provider');

        $event->update(['status' => EventStatus::CANCELLED]);

        // TODO: Notify booked clients

        return back()->with('success', 'Event cancelled. All attendees will be notified.');
    }

    public function toggleActive(Event $event): RedirectResponse
    {
        $provider = Auth::user()->provider;

        // Ensure the event belongs to this provider
        if ($event->provider_id !== $provider->id) {
            abort(403);
        }

        $event->update(['is_active' => ! $event->is_active]);

        $status = $event->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Event {$status} successfully.");
    }
}
