<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSiteBookingController extends Controller
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected SubscriptionService $subscriptionService
    ) {}

    /**
     * Get the provider from the provider site middleware.
     */
    protected function getProvider(): Provider
    {
        return app('providersite.provider');
    }

    /**
     * Show the booking creation page.
     */
    public function create(Request $request): Response
    {
        $provider = $this->getProvider();

        // Load additional data needed for booking
        $provider->load([
            'user:id,name,avatar',
            'primaryLocation.region',
            'subscription',
        ]);

        // Get available dates for the next 30 days
        $startDate = now()->format('Y-m-d');
        $endDate = now()->addDays(30)->format('Y-m-d');
        $availableDates = $this->availabilityService->getAvailableDates($provider, $startDate, $endDate);

        // Calculate tier info for first service (will be recalculated when service is selected)
        $firstService = $provider->services->first();
        $tierInfo = $firstService
            ? $this->subscriptionService->calculateFees($provider, (float) $firstService->price)
            : null;

        return Inertia::render('ProviderSite/Book', [
            'provider' => [
                'id' => $provider->id,
                'business_name' => $provider->business_name,
                'slug' => $provider->slug,
                'avatar' => $provider->user?->avatar,
                'location' => $provider->primaryLocation?->display_name,
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
                'category' => $service->category ? [
                    'id' => $service->category->id,
                    'name' => $service->category->name,
                    'icon' => $service->category->icon,
                ] : null,
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
        $provider = $this->getProvider();

        $request->validate([
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $service = Service::findOrFail($request->service_id);

        // Verify service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $slots = $this->availabilityService->getAvailableSlots($provider, $service, $request->date);

        return response()->json(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action): RedirectResponse
    {
        $provider = $this->getProvider();
        $service = Service::findOrFail($request->service_id);

        // Verify service belongs to this provider
        if ($service->provider_id !== $provider->id) {
            return back()->withErrors(['service' => 'Service not found']);
        }

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
                $request->notes
            );

            // Redirect to provider site confirmation or main platform dashboard
            if ($request->user()) {
                return redirect()
                    ->route('client.bookings.show', $booking->uuid)
                    ->with('success', $this->getSuccessMessage($booking));
            }

            return redirect()
                ->route('providersite.book.confirmation', [
                    'provider' => $provider->slug,
                    'uuid' => $booking->uuid,
                ])
                ->with('success', $this->getSuccessMessage($booking));
        } catch (\Exception $e) {
            return back()->withErrors(['slot' => $e->getMessage()]);
        }
    }

    /**
     * Show booking confirmation page.
     */
    public function confirmation(string $provider, string $uuid): Response
    {
        $providerModel = $this->getProvider();

        $booking = Booking::where('uuid', $uuid)
            ->where('provider_id', $providerModel->id)
            ->with([
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'provider.primaryLocation.region',
                'service:id,name,description,duration_minutes',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('ProviderSite/Confirmation', [
            'booking' => $this->formatBookingForShow($booking),
        ]);
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
        return app(PaymentController::class)->checkout($bookingUuid);
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
                'location' => $booking->provider->primaryLocation?->display_name,
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
            'is_past' => $booking->isPast(),
            'is_today' => $booking->isToday(),
            'can_cancel' => $booking->canBeCancelled(),
            'confirmed_at' => $booking->confirmed_at?->format('M j, Y g:i A'),
            'created_at' => $booking->created_at->format('M j, Y g:i A'),
            // Payment/deposit info
            'is_guest_booking' => $booking->isGuestBooking(),
            'client_name' => $booking->client_name,
            'client_email' => $booking->client_email,
            'requires_deposit' => $booking->requiresDeposit(),
            'deposit_amount' => (float) $booking->deposit_amount,
            'deposit_paid' => $booking->isDepositPaid(),
            'balance_amount' => $booking->balance_amount,
            'can_pay' => $booking->canProceedToPayment(),
            'payment' => $booking->payment ? [
                'status' => $booking->payment->status,
                'amount' => (float) $booking->payment->amount,
                'payment_type' => $booking->payment->payment_type ?? 'full',
                'paid_at' => $booking->payment->paid_at?->format('M j, Y g:i A'),
            ] : null,
        ];
    }
}
