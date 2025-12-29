<?php

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Payment\Services\FeeCalculator;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Service\Models\Service;
use App\Mail\BookingCreated;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateBookingAction
{
    public function __construct(
        protected AvailabilityService $availabilityService,
        protected FeeCalculator $feeCalculator
    ) {}

    /**
     * Create a booking for an authenticated user.
     */
    public function execute(
        User $client,
        Provider $provider,
        Service $service,
        string $date,
        string $startTime,
        ?string $notes = null,
        ?TeamMember $teamMember = null
    ): Booking {
        return $this->createBooking(
            provider: $provider,
            service: $service,
            date: $date,
            startTime: $startTime,
            notes: $notes,
            client: $client,
            teamMember: $teamMember
        );
    }

    /**
     * Create a booking for a guest user.
     */
    public function executeForGuest(
        Provider $provider,
        Service $service,
        string $date,
        string $startTime,
        string $guestEmail,
        string $guestName,
        string $guestPhone,
        ?string $notes = null,
        ?TeamMember $teamMember = null
    ): Booking {
        return $this->createBooking(
            provider: $provider,
            service: $service,
            date: $date,
            startTime: $startTime,
            notes: $notes,
            guestEmail: $guestEmail,
            guestName: $guestName,
            guestPhone: $guestPhone,
            teamMember: $teamMember
        );
    }

    /**
     * Create the booking with tier-based fee calculation.
     */
    protected function createBooking(
        Provider $provider,
        Service $service,
        string $date,
        string $startTime,
        ?string $notes = null,
        ?User $client = null,
        ?string $guestEmail = null,
        ?string $guestName = null,
        ?string $guestPhone = null,
        ?TeamMember $teamMember = null
    ): Booking {
        // Calculate end time based on service duration
        $endTime = Carbon::parse($startTime)
            ->addMinutes($service->duration_minutes)
            ->format('H:i');

        // Get buffer minutes for availability checking
        $bufferMinutes = $service->getEffectiveBufferMinutes();

        // Verify slot is still available (with team member and buffer support)
        if (! $this->availabilityService->isSlotAvailable(
            $provider,
            $date,
            $startTime,
            $endTime,
            $teamMember,
            $bufferMinutes
        )) {
            throw new \Exception('This time slot is no longer available.');
        }

        // Calculate fees using service's deposit settings
        $fees = $this->feeCalculator->calculateFees($provider, (float) $service->price, $service)->toArray();

        // Get effective booking settings
        $settings = $service->getEffectiveBookingSettings();
        $requiresApproval = $settings['requires_approval'];

        // Determine initial status based on tier and settings
        $initialStatus = $this->determineInitialStatus($fees, $requiresApproval);

        $booking = Booking::create([
            'uuid' => Str::uuid(),
            'client_id' => $client?->id,
            'provider_id' => $provider->id,
            'team_member_id' => $teamMember?->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $initialStatus,
            'service_price' => $fees['service_price'],
            'platform_fee' => $fees['total_fee_rate'], // Legacy field (percentage)
            'total_amount' => $fees['client_pays'], // What client pays (includes convenience fee if applicable)
            'client_notes' => $notes,
            // Guest fields
            'guest_email' => $guestEmail,
            'guest_name' => $guestName,
            'guest_phone' => $guestPhone,
            // Fee tracking (legacy)
            'deposit_amount' => $fees['deposit_amount'],
            'deposit_paid' => false,
            'platform_fee_amount' => $fees['platform_fee'],
            // New separated fee structure
            'zeen_fee' => $fees['zeen_fee'],
            'gateway_fee' => $fees['gateway_fee'],
            'convenience_fee' => $fees['convenience_fee'],
            'fee_payer' => $fees['fee_payer'],
            // Set confirmed_at if auto-confirmed
            'confirmed_at' => $initialStatus === BookingStatus::CONFIRMED ? now() : null,
        ]);

        // Load relationships for email
        $booking->load(['client', 'provider.user', 'service', 'teamMember']);

        // Get client email (either from user or guest)
        $clientEmail = $client?->email ?? $guestEmail;

        // Send notification emails
        if ($clientEmail) {
            Mail::to($clientEmail)->send(new BookingCreated($booking, 'client'));
        }
        Mail::to($provider->user->email)->send(new BookingCreated($booking, 'provider'));

        return $booking;
    }

    /**
     * Determine the initial booking status based on tier rules.
     *
     * - Enterprise (no deposit) + no approval = CONFIRMED
     * - No deposit + no approval = CONFIRMED
     * - Requires deposit or approval = PENDING
     */
    protected function determineInitialStatus(array $fees, bool $requiresApproval): BookingStatus
    {
        // If approval is required, always start as pending
        if ($requiresApproval) {
            return BookingStatus::PENDING;
        }

        // If no deposit required and no approval needed, auto-confirm
        if (! $fees['requires_deposit']) {
            return BookingStatus::CONFIRMED;
        }

        // Deposit required but no approval needed - pending until deposit paid
        return BookingStatus::PENDING;
    }
}
