<?php

namespace App\Domains\Booking\Actions;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Booking\Services\AvailabilityService;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Mail\BookingCreated;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateBookingAction
{
    public function __construct(
        protected AvailabilityService $availabilityService
    ) {}

    public function execute(
        User $client,
        Provider $provider,
        Service $service,
        string $date,
        string $startTime,
        ?string $notes = null
    ): Booking {
        // Calculate end time based on service duration
        $endTime = Carbon::parse($startTime)
            ->addMinutes($service->duration_minutes)
            ->format('H:i');

        // Verify slot is still available
        if (! $this->availabilityService->isSlotAvailable($provider, $date, $startTime, $endTime)) {
            throw new \Exception('This time slot is no longer available.');
        }

        // Calculate pricing
        $pricing = $this->availabilityService->calculatePricing(
            $service,
            (float) $provider->commission_rate / 100
        );

        $booking = Booking::create([
            'uuid' => Str::uuid(),
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'service_id' => $service->id,
            'booking_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => BookingStatus::PENDING,
            'service_price' => $pricing['service_price'],
            'platform_fee' => $pricing['platform_fee'],
            'total_amount' => $pricing['total_amount'],
            'client_notes' => $notes,
        ]);

        // Load relationships for email
        $booking->load(['client', 'provider', 'service']);

        // Send notification emails
        Mail::to($client->email)->send(new BookingCreated($booking, 'client'));
        Mail::to($provider->user->email)->send(new BookingCreated($booking, 'provider'));

        return $booking;
    }
}
