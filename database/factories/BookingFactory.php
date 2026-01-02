<?php

namespace Database\Factories;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Service\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $bookingDate = fake()->dateTimeBetween('-3 months', '+2 months');
        $startHour = fake()->numberBetween(9, 17);
        $startMinute = fake()->randomElement([0, 15, 30, 45]);
        $duration = fake()->randomElement([30, 45, 60, 90, 120]);

        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
        $endTime = date('H:i:s', strtotime($startTime) + ($duration * 60));

        $servicePrice = fake()->randomFloat(2, 20, 200);
        $platformFee = round($servicePrice * 0.03, 2);
        $totalAmount = $servicePrice + $platformFee;

        return [
            'uuid' => Str::uuid()->toString(),
            'client_id' => User::factory(),
            'provider_id' => Provider::factory(),
            'team_member_id' => null,
            'service_id' => Service::factory(),
            'booking_date' => $bookingDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => BookingStatus::CONFIRMED,
            'service_price' => $servicePrice,
            'platform_fee' => $platformFee,
            'total_amount' => $totalAmount,
            'client_notes' => fake()->optional(0.3)->sentence(),
            'provider_notes' => fake()->optional(0.2)->sentence(),
            'cancellation_reason' => null,
            'confirmed_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
            'guest_email' => null,
            'guest_name' => null,
            'guest_phone' => null,
            'deposit_amount' => fake()->optional(0.3)->randomFloat(2, 10, 50),
            'deposit_paid' => false,
            'zeen_fee' => round($servicePrice * 0.03, 2),
            'gateway_fee' => round($servicePrice * 0.029, 2),
            'convenience_fee' => 0,
            'fee_payer' => fake()->randomElement(['client', 'provider', 'split']),
            'created_at' => $bookingDate,
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::PENDING,
            'confirmed_at' => null,
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::CONFIRMED,
            'confirmed_at' => now(),
            'completed_at' => null,
            'cancelled_at' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $bookingDate = fake()->dateTimeBetween('-3 months', '-1 day');

            return [
                'status' => BookingStatus::COMPLETED,
                'booking_date' => $bookingDate,
                'confirmed_at' => $bookingDate,
                'completed_at' => $bookingDate,
                'cancelled_at' => null,
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->randomElement([
                'Client requested cancellation',
                'Schedule conflict',
                'Personal emergency',
                'No longer needed',
            ]),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(function (array $attributes) {
            $bookingDate = fake()->dateTimeBetween('-3 months', '-1 day');

            return [
                'status' => BookingStatus::NO_SHOW,
                'booking_date' => $bookingDate,
                'confirmed_at' => $bookingDate,
                'completed_at' => null,
                'cancelled_at' => null,
            ];
        });
    }

    // =========================================================================
    // Time States
    // =========================================================================

    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $bookingDate = fake()->dateTimeBetween('-6 months', '-1 day');

            return [
                'booking_date' => $bookingDate,
                'created_at' => $bookingDate,
            ];
        });
    }

    public function future(): static
    {
        return $this->state(function (array $attributes) {
            $bookingDate = fake()->dateTimeBetween('+1 day', '+2 months');

            return [
                'booking_date' => $bookingDate,
                'status' => BookingStatus::CONFIRMED,
                'created_at' => now(),
            ];
        });
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_date' => now()->toDateString(),
            'status' => BookingStatus::CONFIRMED,
        ]);
    }

    // =========================================================================
    // Guest Booking States
    // =========================================================================

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => null,
            'guest_email' => fake()->safeEmail(),
            'guest_name' => fake()->name(),
            'guest_phone' => fake()->phoneNumber(),
        ]);
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }

    public function forService(Service $service): static
    {
        return $this->state(function (array $attributes) use ($service) {
            $platformFee = round($service->price * 0.03, 2);

            return [
                'service_id' => $service->id,
                'provider_id' => $service->provider_id,
                'service_price' => $service->price,
                'platform_fee' => $platformFee,
                'total_amount' => $service->price + $platformFee,
            ];
        });
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
            'guest_email' => null,
            'guest_name' => null,
            'guest_phone' => null,
        ]);
    }

    public function forTeamMember(TeamMember $teamMember): static
    {
        return $this->state(fn (array $attributes) => [
            'team_member_id' => $teamMember->id,
            'provider_id' => $teamMember->provider_id,
        ]);
    }

    // =========================================================================
    // Deposit States
    // =========================================================================

    public function withDeposit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'deposit_amount' => $amount ?? fake()->randomFloat(2, 20, 50),
            'deposit_paid' => true,
        ]);
    }

    public function withUnpaidDeposit(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'deposit_amount' => $amount ?? fake()->randomFloat(2, 20, 50),
            'deposit_paid' => false,
        ]);
    }
}
