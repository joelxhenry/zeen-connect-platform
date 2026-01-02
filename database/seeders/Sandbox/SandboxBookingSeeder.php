<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SandboxBookingSeeder extends Seeder
{
    /**
     * Seed bookings and payments for active providers.
     */
    public function run(): void
    {
        // Get active providers with their services and subscriptions
        $providers = Provider::with(['services', 'subscription'])
            ->where('status', 'active')
            ->has('services')
            ->get();

        // Get client users for bookings
        $clients = User::where('role', 'client')
            ->where('is_active', true)
            ->get();

        if ($clients->isEmpty()) {
            return;
        }

        foreach ($providers as $provider) {
            $tier = $provider->subscription?->tier ?? SubscriptionTier::STARTER;

            // Determine booking count based on tier
            $bookingCount = match ($tier) {
                SubscriptionTier::STARTER => fake()->numberBetween(10, 30),
                SubscriptionTier::PREMIUM => fake()->numberBetween(30, 80),
                SubscriptionTier::ENTERPRISE => fake()->numberBetween(80, 200),
            };

            $this->createBookingsForProvider($provider, $clients, $bookingCount);
        }
    }

    protected function createBookingsForProvider(Provider $provider, $clients, int $count): void
    {
        $services = $provider->services;

        if ($services->isEmpty()) {
            return;
        }

        // Status distribution: 50% completed, 25% confirmed, 10% pending, 10% cancelled, 5% no-show
        $statusWeights = [
            BookingStatus::COMPLETED->value => 50,
            BookingStatus::CONFIRMED->value => 25,
            BookingStatus::PENDING->value => 10,
            BookingStatus::CANCELLED->value => 10,
            BookingStatus::NO_SHOW->value => 5,
        ];

        for ($i = 0; $i < $count; $i++) {
            $service = $services->random();
            $status = BookingStatus::from($this->getWeighted($statusWeights));

            // 15% guest bookings
            $isGuest = fake()->boolean(15);

            // Determine booking date based on status
            $bookingDate = match ($status) {
                BookingStatus::COMPLETED, BookingStatus::NO_SHOW => fake()->dateTimeBetween('-6 months', '-1 day'),
                BookingStatus::CANCELLED => fake()->dateTimeBetween('-3 months', '+1 month'),
                BookingStatus::CONFIRMED => fake()->dateTimeBetween('-1 week', '+2 months'),
                BookingStatus::PENDING => fake()->dateTimeBetween('now', '+2 months'),
            };

            // Generate time slots
            $startHour = fake()->numberBetween(9, 17);
            $startMinute = fake()->randomElement([0, 15, 30, 45]);
            $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
            $endTime = date('H:i:s', strtotime($startTime) + ($service->duration_minutes * 60));

            // Calculate pricing
            $servicePrice = (float) $service->price;
            $platformFee = round($servicePrice * 0.03, 2);
            $totalAmount = $servicePrice + $platformFee;

            // Create booking data
            $bookingData = [
                'uuid' => Str::uuid()->toString(),
                'provider_id' => $provider->id,
                'service_id' => $service->id,
                'booking_date' => $bookingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $status,
                'service_price' => $servicePrice,
                'platform_fee' => $platformFee,
                'total_amount' => $totalAmount,
                'client_notes' => fake()->optional(0.3)->sentence(),
                'zeen_fee' => round($servicePrice * 0.03, 2),
                'gateway_fee' => round($servicePrice * 0.029, 2),
                'convenience_fee' => 0,
                'fee_payer' => fake()->randomElement(['client', 'provider']),
            ];

            // Handle guest vs registered client
            if ($isGuest) {
                $bookingData['client_id'] = null;
                $bookingData['guest_email'] = fake()->safeEmail();
                $bookingData['guest_name'] = fake()->name();
                $bookingData['guest_phone'] = fake()->phoneNumber();
            } else {
                $bookingData['client_id'] = $clients->random()->id;
            }

            // Set status-specific fields
            if ($status === BookingStatus::CONFIRMED || $status === BookingStatus::COMPLETED || $status === BookingStatus::NO_SHOW) {
                $bookingData['confirmed_at'] = $bookingDate;
            }

            if ($status === BookingStatus::COMPLETED) {
                $bookingData['completed_at'] = $bookingDate;
            }

            if ($status === BookingStatus::CANCELLED) {
                $bookingData['cancelled_at'] = now();
                $bookingData['cancellation_reason'] = fake()->randomElement([
                    'Client requested cancellation',
                    'Schedule conflict',
                    'Personal emergency',
                    'No longer needed',
                ]);
            }

            $booking = Booking::create($bookingData);

            // Create payment for completed bookings (80% have payments)
            if ($status === BookingStatus::COMPLETED && fake()->boolean(80)) {
                $this->createPaymentForBooking($booking);
            }
        }
    }

    protected function createPaymentForBooking(Booking $booking): void
    {
        $amount = (float) $booking->total_amount;
        $platformFee = round($amount * 0.03, 2);
        $processingFee = round($amount * 0.029 + 0.30, 2);
        $providerAmount = $amount - $platformFee - $processingFee;

        Payment::create([
            'uuid' => Str::uuid()->toString(),
            'booking_id' => $booking->id,
            'client_id' => $booking->client_id,
            'provider_id' => $booking->provider_id,
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'provider_amount' => max(0, $providerAmount),
            'currency' => 'JMD',
            'payment_type' => 'full',
            'processing_fee' => $processingFee,
            'processing_fee_payer' => fake()->randomElement(['client', 'provider']),
            'gateway' => 'wipay',
            'gateway_type' => 'card',
            'gateway_provider' => 'wipay',
            'gateway_transaction_id' => 'sandbox_txn_' . Str::random(16),
            'gateway_order_id' => 'sandbox_order_' . Str::random(12),
            'gateway_response_code' => '00',
            'gateway_response' => ['status' => 'approved', 'sandbox' => true],
            'status' => PaymentStatus::COMPLETED,
            'card_last_four' => fake()->numerify('####'),
            'card_brand' => fake()->randomElement(['visa', 'mastercard']),
            'paid_at' => $booking->completed_at ?? now(),
        ]);
    }

    protected function getWeighted(array $weights): string
    {
        $rand = fake()->numberBetween(1, 100);
        $cumulative = 0;

        foreach ($weights as $key => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
