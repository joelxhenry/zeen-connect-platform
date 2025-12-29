<?php

namespace App\Domains\Booking\Controllers;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Resources\BookingResource;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Services\FeeCalculator;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientBookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected FeeCalculator $feeCalculator
    ) {}

    /**
     * Display client's booking history.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $status = $request->get('status', 'all');

        $query = Booking::forClient($user->id)
            ->with([
                'provider',
                'service',
            ]);

        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'past') {
            $query->past();
        } else {
            $query->orderByDesc('booking_date')->orderByDesc('start_time');
        }

        $bookings = $query->paginate(10)->withQueryString();

        // Transform using BookingResource
        $bookings->getCollection()->transform(
            fn($booking) => (new BookingResource($booking))
                ->withClient(false)
                ->withProvider()
                ->resolve()
        );

        return Inertia::render('Client/Bookings/Index', [
            'bookings' => $bookings,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show the booking creation page.
     */
    public function create(Request $request): Response
    {
        $provider = Provider::where('slug', $request->provider)
            ->active()
            ->with([
                'user:id,name,avatar',
                'services' => fn($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'services.category:id,name,icon',
                'subscription',
                'teamMembers' => fn($q) => $q->active(),
            ])
            ->firstOrFail();

        // Get available dates for the next 30 days
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        $availableDates = $this->availabilityService->getAvailableDates($provider, $startDate, $endDate);

        // Set provider relationship on each service to avoid lazy loading
        // when getEffectiveBookingSettings() accesses $this->provider
        $provider->services->each(fn($service) => $service->setRelation('provider', $provider));

        // Calculate tier info for first service (will be recalculated when service is selected)
        $firstService = $provider->services->first();
        $tierInfo = $firstService
            ? $this->feeCalculator->calculateFees($provider, (float) $firstService->price, $firstService)->toArray()
            : null;

        return Inertia::render('Booking/Create', [
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
                'avatar' => $provider->user?->avatar,
                'tier' => $tierInfo['tier'] ?? 'free',
                'tier_label' => $tierInfo['tier_label'] ?? 'Free',
            ],
            'services' => $provider->services->map(fn($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'duration_minutes' => $service->duration_minutes,
                'duration_display' => $service->duration_display,
                'price' => (float) $service->price,
                'price_display' => $service->price_display,
                'category' => [
                    'id' => $service->category->id,
                    'name' => $service->category->name,
                    'icon' => $service->category->icon,
                ],
                // Pre-calculate fees for each service (uses service's deposit settings)
                'fees' => $this->feeCalculator->calculateFees($provider, (float) $service->price, $service)->toArray(),
            ]),
            'availableDates' => $availableDates,
            'preselectedService' => $request->service ? (int) $request->service : null,
            'isAuthenticated' => (bool) $request->user(),
            'user' => $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
            ] : null,
            'teamMembers' => $provider->teamMembers->map(fn ($member) => [
                'id' => $member->id,
                'uuid' => $member->uuid,
                'name' => $member->display_name,
                'avatar' => $member->avatar,
            ]),
        ]);
    }

    /**
     * Get available time slots for a date and service.
     */
    public function getSlots(Request $request): JsonResponse
    {
        $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
            'team_member_id' => 'nullable|exists:team_members,id',
        ]);

        $provider = Provider::findOrFail($request->provider_id);
        $service = Service::findOrFail($request->service_id);

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

        return response()->json(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action): RedirectResponse
    {
        $provider = Provider::findOrFail($request->provider_id);
        // Load service with provider to ensure getEffectiveBookingSettings() works correctly
        $service = Service::with('provider')->findOrFail($request->service_id);

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

                // For guests, redirect to public booking confirmation page
                return redirect()
                    ->route('booking.confirmation', $booking->uuid)
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

            return redirect()
                ->route('client.bookings.show', $booking->uuid)
                ->with('success', $this->getSuccessMessage($booking));
        } catch (\Exception $e) {
            return back()->withErrors(['slot' => $e->getMessage()]);
        }
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
     * Show a specific booking.
     */
    public function show(string $uuid): Response
    {
        $booking = Booking::where('uuid', $uuid)
            ->where('client_id', auth()->id())
            ->with([
                'client',
                'provider:id,uuid,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,uuid,name,description,duration_minutes,price',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('Client/Bookings/Show', [
            'booking' => (new BookingResource($booking))
                ->withProvider()
                ->withPayment()
                ->resolve(),
        ]);
    }

    /**
     * Show booking confirmation page (for guests).
     */
    public function confirmation(string $uuid): Response
    {
        $booking = Booking::where('uuid', $uuid)
            ->with([
                'client',
                'provider:id,uuid,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,uuid,name,description,duration_minutes,price',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('Booking/Confirmation', [
            'booking' => (new BookingResource($booking))
                ->withProvider()
                ->withPayment()
                ->resolve(),
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancel(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking = Booking::where('uuid', $uuid)
            ->where('client_id', auth()->id())
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
     * Cancel a guest booking.
     */
    public function cancelGuest(Request $request, string $uuid): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
            'email' => 'required|email',
        ]);

        $booking = Booking::where('uuid', $uuid)
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
}
