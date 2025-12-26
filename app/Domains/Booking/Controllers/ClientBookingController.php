<?php

namespace App\Domains\Booking\Controllers;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Services\SubscriptionService;
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
        protected SubscriptionService $subscriptionService
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
                'provider:id,business_name,slug',
                'provider.user:id,avatar',
                'service:id,name,duration_minutes',
            ]);

        if ($status === 'upcoming') {
            $query->upcoming();
        } elseif ($status === 'past') {
            $query->past();
        } else {
            $query->orderByDesc('booking_date')->orderByDesc('start_time');
        }

        $bookings = $query->paginate(10)->withQueryString();

        // Transform for frontend
        $bookings->getCollection()->transform(fn ($booking) => [
            'id' => $booking->id,
            'uuid' => $booking->uuid,
            'provider' => [
                'business_name' => $booking->provider->business_name,
                'slug' => $booking->provider->slug,
                'avatar' => $booking->provider->user?->avatar,
            ],
            'service' => [
                'name' => $booking->service->name,
                'duration_minutes' => $booking->service->duration_minutes,
            ],
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'formatted_date' => $booking->formatted_date,
            'formatted_time' => $booking->formatted_time,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_color' => $booking->status->color(),
            'total_display' => $booking->total_display,
            'is_past' => $booking->isPast(),
            'is_today' => $booking->isToday(),
            'can_cancel' => $booking->canBeCancelled(),
            // Payment info
            'requires_deposit' => $booking->requiresDeposit(),
            'deposit_amount' => $booking->deposit_amount,
            'deposit_paid' => $booking->isDepositPaid(),
            'can_pay' => $booking->canProceedToPayment(),
        ]);

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
                'services' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order'),
                'services.category:id,name,icon',
                'subscription',
            ])
            ->firstOrFail();

        // Get available dates for the next 30 days
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        $availableDates = $this->availabilityService->getAvailableDates($provider, $startDate, $endDate);

        // Calculate tier info for first service (will be recalculated when service is selected)
        $firstService = $provider->services->first();
        $tierInfo = $firstService
            ? $this->subscriptionService->calculateFees($provider, (float) $firstService->price)
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
            'services' => $provider->services->map(fn ($service) => [
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
                // Pre-calculate fees for each service
                'fees' => $this->subscriptionService->calculateFees($provider, (float) $service->price),
            ]),
            'availableDates' => $availableDates,
            'preselectedService' => $request->service ? (int) $request->service : null,
            'isAuthenticated' => (bool) $request->user(),
            'user' => $request->user() ? [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
            ] : null,
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
        ]);

        $provider = Provider::findOrFail($request->provider_id);
        $service = Service::findOrFail($request->service_id);

        $slots = $this->availabilityService->getAvailableSlots($provider, $service, $request->date);

        return response()->json(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action): RedirectResponse
    {
        $provider = Provider::findOrFail($request->provider_id);
        $service = Service::findOrFail($request->service_id);

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
                    notes: $request->notes
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
                $request->notes
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
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,name,description,duration_minutes',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('Client/Bookings/Show', [
            'booking' => $this->formatBookingForShow($booking),
        ]);
    }

    /**
     * Show booking confirmation page (for guests).
     */
    public function confirmation(string $uuid): Response
    {
        $booking = Booking::where('uuid', $uuid)
            ->with([
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,name,description,duration_minutes',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('Booking/Confirmation', [
            'booking' => $this->formatBookingForShow($booking),
        ]);
    }

    /**
     * Format booking data for show/confirmation views.
     */
    protected function formatBookingForShow(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'uuid' => $booking->uuid,
            'provider' => [
                'business_name' => $booking->provider->business_name,
                'slug' => $booking->provider->slug,
                'avatar' => $booking->provider->user?->avatar,
                'address' => $booking->provider->address,
            ],
            'service' => [
                'name' => $booking->service->name,
                'description' => $booking->service->description,
                'duration_minutes' => $booking->service->duration_minutes,
            ],
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'formatted_date' => $booking->formatted_date,
            'formatted_time' => $booking->formatted_time,
            'status' => $booking->status->value,
            'status_label' => $booking->status->label(),
            'status_color' => $booking->status->color(),
            'service_price' => (float) $booking->service_price,
            'total_amount' => (float) $booking->total_amount,
            'total_display' => $booking->total_display,
            'client_notes' => $booking->client_notes,
            'provider_notes' => $booking->provider_notes,
            'cancellation_reason' => $booking->cancellation_reason,
            'is_past' => $booking->isPast(),
            'is_today' => $booking->isToday(),
            'can_cancel' => $booking->canBeCancelled(),
            'confirmed_at' => $booking->confirmed_at?->format('M j, Y g:i A'),
            'completed_at' => $booking->completed_at?->format('M j, Y g:i A'),
            'cancelled_at' => $booking->cancelled_at?->format('M j, Y g:i A'),
            'created_at' => $booking->created_at->format('M j, Y g:i A'),
            // Payment/deposit info
            'is_guest_booking' => $booking->isGuestBooking(),
            'client_name' => $booking->client_name,
            'client_email' => $booking->client_email,
            'requires_deposit' => $booking->requiresDeposit(),
            'deposit_amount' => (float) $booking->deposit_amount,
            'deposit_paid' => $booking->isDepositPaid(),
            'balance_amount' => $booking->balance_amount,
            'platform_fee_amount' => (float) $booking->platform_fee_amount,
            'processing_fee_amount' => (float) $booking->processing_fee_amount,
            'can_pay' => $booking->canProceedToPayment(),
            'payment' => $booking->payment ? [
                'status' => $booking->payment->status,
                'amount' => (float) $booking->payment->amount,
                'payment_type' => $booking->payment->payment_type ?? 'full',
                'paid_at' => $booking->payment->paid_at?->format('M j, Y g:i A'),
            ] : null,
        ];
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
