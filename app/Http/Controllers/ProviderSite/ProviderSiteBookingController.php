<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Resources\BookingResource;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Service\Resources\ServiceResource;
use App\Domains\Subscription\Services\SubscriptionService;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        return app('site.provider');
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
            'subscription',
            'services.category',
            'services.provider',
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
                'tier' => Arr::get($tierInfo, 'tier', 'free'),
                'tier_label' => Arr::get($tierInfo, 'tier_label', 'Free'),
            ],
            'services' => $provider->services->map(
                fn ($service) => (new ServiceResource($service))->withCategory()->withFees()->resolve()
            ),
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
            return ApiResponse::notFound('Service not found');
        }

        $slots = $this->availabilityService->getAvailableSlots($provider, $service, $request->date);

        return ApiResponse::success(['slots' => $slots]);
    }

    /**
     * Store a new booking.
     */
    public function store(StoreBookingRequest $request, CreateBookingAction $action)
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
                session()->put('booking_success_message', $this->getSuccessMessage($booking));
                return inertia()->location(
                    route('client.bookings.show', $booking->uuid)
                );
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
                'client',
                'provider:id,business_name,slug,address',
                'provider.user:id,name,avatar,email',
                'service:id,uuid,name,description,duration_minutes,price',
                'payment',
            ])
            ->firstOrFail();

        return Inertia::render('ProviderSite/Confirmation', [
            'booking' => (new BookingResource($booking))
                ->withProvider()
                ->withPayment()
                ->resolve(),
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

}
