<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Event\Enums\EventBookingStatus;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\OccurrenceStatus;
use App\Domains\Event\Models\Event;
use App\Domains\Event\Models\EventBooking;
use App\Domains\Event\Models\EventOccurrence;
use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\ProviderSite\Enums\TemplateType;
use App\Domains\ProviderSite\Services\ProviderSiteDataService;
use App\Domains\ProviderSite\Services\TemplateResolver;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSiteBookingController extends Controller
{
    public function __construct(
        protected ProviderSiteDataService $dataService,
        protected TemplateResolver $templateResolver,
        protected AvailabilityService $availabilityService
    ) {}

    /**
     * Get the provider from the provider site middleware.
     */
    protected function getProvider(): Provider
    {
        return app('site.provider');
    }

    /**
     * Show the booking creation page.
     * Handles both service bookings and event bookings based on URL params.
     */
    public function create(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);

        // Check if this is an event booking request
        if ($request->has('event')) {
            return $this->createEventBooking($request, $provider, $template);
        }

        // Default to service booking
        $data = $this->dataService->getBookingPageData(
            $provider,
            $request->service ? (int) $request->service : null,
            $request->user()
        );

        $data['bookingType'] = 'service';

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Book'),
            $data
        );
    }

    /**
     * Show the event booking page.
     */
    protected function createEventBooking(Request $request, Provider $provider, TemplateType $template): Response
    {
        $event = Event::where('provider_id', $provider->id)
            ->where('id', (int) $request->event)
            ->where('is_active', true)
            ->where('status', EventStatus::PUBLISHED)
            ->firstOrFail();

        $data = $this->dataService->getEventBookingPageData(
            $provider,
            $event,
            $request->occurrence ? (int) $request->occurrence : null,
            $request->user()
        );

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Book'),
            $data
        );
    }

    /**
     * Get available time slots for a date and service.
     */
    public function getSlots(Request $request): JsonResponse
    {
        $provider = $this->getProvider();

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'team_member_id' => 'nullable|exists:team_members,id',
        ]);

        $service = Service::findOrFail($request->service_id);

        // Verify service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            return ApiResponse::notFound('Service not found');
        }

        // Load team member if specified
        $teamMember = $request->team_member_id
            ? TeamMember::where('id', $request->team_member_id)
            ->where('provider_id', $provider->id)
            ->active()
            ->first()
            : null;

        $slots = $this->availabilityService->getAvailableSlots(
            $provider,
            $service,
            $request->date,
            $teamMember
        );

        return ApiResponse::success(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action)
    {
        $provider = $this->getProvider();
        // Load service with provider to ensure getEffectiveBookingSettings() works correctly
        $service = Service::with('provider')->findOrFail($request->service_id);

        // Verify service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            return back()->withErrors(['service' => 'Service not found']);
        }

        // Load team member if specified
        $teamMember = $request->team_member_id
            ? TeamMember::where('id', $request->team_member_id)
            ->where('provider_id', $provider->id)
            ->active()
            ->first()
            : null;

        try {
            // Handle guest vs authenticated booking
            if ($request->isGuestBooking()) {
                $booking = $action->executeForGuest(
                    provider: $provider,
                    service: $service,
                    date: $request->date,
                    startTime: $request->start_time,
                    guestEmail: $request->guest_email,
                    guestName: $request->guest_name,
                    guestPhone: $request->guest_phone,
                    notes: $request->notes,
                    teamMember: $teamMember
                );

                // Redirect to provider site confirmation
                return redirect()
                    ->route('providersite.book.confirmation', [
                        'provider' => $provider->slug,
                        'uuid' => $booking->uuid,
                    ])
                    ->with('success', $this->getSuccessMessage($booking));
            }

            $booking = $action->execute(
                $request->user(),
                $provider,
                $service,
                $request->date,
                $request->start_time,
                $request->notes,
                $teamMember
            );

            // Redirect to provider site confirmation or main platform dashboard


            session()->put('booking_success_message', $this->getSuccessMessage($booking));

            return redirect()
                ->route('providersite.book.confirmation', [
                    'provider' => $provider->domain,
                    'uuid' => $booking->uuid,
                ])
                ->with('success', $this->getSuccessMessage($booking));
        } catch (\Exception $e) {
            return back()->withErrors(['slot' => $e->getMessage()]);
        }
    }

    /**
     * Store a new event booking.
     */
    public function storeEventBooking(Request $request): RedirectResponse
    {
        $provider = $this->getProvider();

        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'occurrence_id' => 'required|exists:event_occurrences,id',
            'spots' => 'required|integer|min:1|max:20',
            'notes' => 'nullable|string|max:500',
            'guest_email' => 'required_without:client_id|email|max:255',
            'guest_name' => 'required_without:client_id|string|max:255',
            'guest_phone' => 'required_without:client_id|string|max:50',
        ]);

        // Verify event belongs to this provider and is bookable
        $event = Event::where('id', $validated['event_id'])
            ->where('provider_id', $provider->id)
            ->where('is_active', true)
            ->where('status', EventStatus::PUBLISHED)
            ->firstOrFail();

        // Verify occurrence belongs to this event and is available
        $occurrence = EventOccurrence::where('id', $validated['occurrence_id'])
            ->where('event_id', $event->id)
            ->where('status', OccurrenceStatus::SCHEDULED)
            ->where('start_datetime', '>', now())
            ->firstOrFail();

        // Check availability
        if ($occurrence->spots_remaining < $validated['spots']) {
            return back()->withErrors([
                'spots' => $occurrence->spots_remaining === 0
                    ? 'This event date is fully booked.'
                    : "Only {$occurrence->spots_remaining} spots remaining.",
            ]);
        }

        try {
            $booking = DB::transaction(function () use ($request, $event, $occurrence, $validated) {
                // Create the event booking
                $booking = EventBooking::create([
                    'event_occurrence_id' => $occurrence->id,
                    'client_id' => $request->user()?->id,
                    'guest_name' => $request->user() ? null : $validated['guest_name'],
                    'guest_email' => $request->user() ? null : $validated['guest_email'],
                    'guest_phone' => $request->user() ? null : $validated['guest_phone'],
                    'spots_booked' => $validated['spots'],
                    'total_amount' => $event->price * $validated['spots'],
                    'deposit_amount' => $event->deposit_amount ? $event->deposit_amount * $validated['spots'] : null,
                    'deposit_paid' => false,
                    'status' => EventBookingStatus::PENDING,
                    'client_notes' => $validated['notes'],
                ]);

                // Decrement spots
                $occurrence->decrementSpots($validated['spots']);

                return $booking;
            });

            return redirect()
                ->route('providersite.book.event-confirmation', [
                    'provider' => $provider->domain,
                    'uuid' => $booking->uuid,
                ])
                ->with('success', $this->getEventSuccessMessage($booking));
        } catch (\Exception $e) {
            return back()->withErrors(['booking' => 'Failed to create booking. Please try again.']);
        }
    }

    /**
     * Show booking confirmation page.
     * Handles both service and event bookings.
     */
    public function confirmation(string $provider, string $uuid): Response
    {
        $providerModel = $this->getProvider();
        $template = $this->templateResolver->resolve($providerModel);
        $data = $this->dataService->getConfirmationPageData($providerModel, $uuid);
        $data['bookingType'] = 'service';

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Confirmation'),
            $data
        );
    }

    /**
     * Show event booking confirmation page.
     */
    public function eventConfirmation(string $provider, string $uuid): Response
    {
        $providerModel = $this->getProvider();
        $template = $this->templateResolver->resolve($providerModel);
        $data = $this->dataService->getEventConfirmationPageData($providerModel, $uuid);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Confirmation'),
            $data
        );
    }

    /**
     * Show the user's bookings for this provider.
     */
    public function myBookings(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getMyBookingsPageData($provider, $request->user());

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'MyBookings'),
            $data
        );
    }

    /**
     * Cancel a guest booking.
     */
    public function cancelGuest(Request $request, string $provider, string $uuid): RedirectResponse
    {
        $providerModel = $this->getProvider();

        $request->validate([
            'reason' => 'required|string|max:500',
            'email' => 'required|email',
        ]);

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $providerModel->id)
            ->whereNull('client_id')
            ->where('guest_email', $request->email)
            ->firstOrFail();

        if (! $booking->canBeCancelled()) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled.']);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
            'cancelled_at' => now(),
        ]);

        return back()->with('success', 'Booking cancelled successfully.');
    }

    /**
     * Redirect to payment checkout.
     */
    public function checkout(Request $request, string $provider, string $bookingUuid)
    {
        // Delegate to the main PaymentController
        return app(PaymentController::class)->checkout($request, $bookingUuid);
    }

    /**
     * Get appropriate success message based on booking state.
     */
    protected function getSuccessMessage(Booking $booking): string
    {
        if ($booking->status->value === 'confirmed') {
            return 'Booking confirmed!';
        }

        if ($booking->requiresDeposit()) {
            return 'Booking created! Please complete the deposit payment to secure your appointment.';
        }

        return 'Booking created successfully! Awaiting provider confirmation.';
    }

    /**
     * Get appropriate success message for event bookings.
     */
    protected function getEventSuccessMessage(EventBooking $booking): string
    {
        if ($booking->isConfirmed()) {
            return 'Event registration confirmed!';
        }

        if ($booking->requiresDeposit()) {
            return 'Registration created! Please complete the deposit payment to secure your spot.';
        }

        return 'Registration created successfully! Awaiting provider confirmation.';
    }
}
