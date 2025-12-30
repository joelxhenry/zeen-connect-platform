<?php

namespace App\Http\Controllers\ProviderSite;

use App\Domains\Booking\Actions\CreateBookingAction;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Requests\StoreBookingRequest;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Controllers\PaymentController;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\ProviderSite\Services\ProviderSiteDataService;
use App\Domains\ProviderSite\Services\TemplateResolver;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     */
    public function create(Request $request): Response
    {
        $provider = $this->getProvider();
        $template = $this->templateResolver->resolve($provider);
        $data = $this->dataService->getBookingPageData(
            $provider,
            $request->service ? (int) $request->service : null,
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
     * Show booking confirmation page.
     */
    public function confirmation(string $provider, string $uuid): Response
    {
        $providerModel = $this->getProvider();
        $template = $this->templateResolver->resolve($providerModel);
        $data = $this->dataService->getConfirmationPageData($providerModel, $uuid);

        return Inertia::render(
            $this->templateResolver->getPagePath($template, 'Confirmation'),
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
}
