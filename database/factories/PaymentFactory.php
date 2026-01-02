<?php

namespace Database\Factories;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    protected array $cardBrands = ['visa', 'mastercard', 'amex', 'discover'];

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 20, 300);
        $platformFee = round($amount * 0.03, 2);
        $processingFee = round($amount * 0.029 + 0.30, 2);
        $providerAmount = $amount - $platformFee - $processingFee;

        return [
            'uuid' => Str::uuid()->toString(),
            'booking_id' => Booking::factory(),
            'client_id' => User::factory(),
            'provider_id' => Provider::factory(),
            'amount' => $amount,
            'platform_fee' => $platformFee,
            'provider_amount' => $providerAmount,
            'currency' => 'JMD',
            'payment_type' => 'deposit',
            'processing_fee' => $processingFee,
            'processing_fee_payer' => fake()->randomElement(['client', 'provider']),
            'gateway' => 'wipay',
            'gateway_type' => 'card',
            'gateway_provider' => 'wipay',
            'gateway_transaction_id' => 'sandbox_txn_' . Str::random(16),
            'gateway_order_id' => 'sandbox_order_' . Str::random(12),
            'gateway_response_code' => '00',
            'gateway_response' => ['status' => 'approved', 'sandbox' => true],
            'split_transaction_id' => null,
            'split_details' => null,
            'ledger_entry_id' => null,
            'status' => PaymentStatus::COMPLETED,
            'failure_reason' => null,
            'card_last_four' => fake()->numerify('####'),
            'card_brand' => fake()->randomElement($this->cardBrands),
            'paid_at' => now(),
            'refunded_at' => null,
            'is_refunded' => false,
            'refund_reason' => null,
            'refund_transaction_id' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::COMPLETED,
            'paid_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PENDING,
            'paid_at' => null,
            'gateway_transaction_id' => null,
            'failure_reason' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'paid_at' => null,
            'gateway_response_code' => fake()->randomElement(['05', '51', '54', '57']),
            'failure_reason' => fake()->randomElement([
                'Card declined',
                'Insufficient funds',
                'Card expired',
                'Transaction not permitted',
                'Gateway timeout',
            ]),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REFUNDED,
            'paid_at' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'refunded_at' => now(),
            'is_refunded' => true,
            'refund_reason' => fake()->randomElement([
                'Client requested refund',
                'Service not provided',
                'Duplicate payment',
                'Provider cancelled',
            ]),
            'refund_transaction_id' => 'sandbox_refund_' . Str::random(16),
        ]);
    }

    // =========================================================================
    // Payment Type States
    // =========================================================================

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'deposit',
        ]);
    }

    public function fullPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'full',
        ]);
    }

    public function balance(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_type' => 'balance',
        ]);
    }

    // =========================================================================
    // Gateway States
    // =========================================================================

    public function wipay(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'wipay',
            'gateway_provider' => 'wipay',
        ]);
    }

    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'gateway_provider' => 'stripe',
            'gateway_transaction_id' => 'pi_sandbox_' . Str::random(24),
        ]);
    }

    // =========================================================================
    // Card States
    // =========================================================================

    public function visa(): static
    {
        return $this->state(fn (array $attributes) => [
            'card_brand' => 'visa',
            'card_last_four' => '4242',
        ]);
    }

    public function mastercard(): static
    {
        return $this->state(fn (array $attributes) => [
            'card_brand' => 'mastercard',
            'card_last_four' => '5555',
        ]);
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forBooking(Booking $booking): static
    {
        return $this->state(function (array $attributes) use ($booking) {
            $amount = $booking->total_amount;
            $platformFee = round($amount * 0.03, 2);
            $processingFee = round($amount * 0.029 + 0.30, 2);

            return [
                'booking_id' => $booking->id,
                'client_id' => $booking->client_id,
                'provider_id' => $booking->provider_id,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'provider_amount' => $amount - $platformFee - $processingFee,
                'processing_fee' => $processingFee,
            ];
        });
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }

    public function forClient(User $client): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $client->id,
        ]);
    }

    // =========================================================================
    // Amount States
    // =========================================================================

    public function withAmount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $platformFee = round($amount * 0.03, 2);
            $processingFee = round($amount * 0.029 + 0.30, 2);

            return [
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'provider_amount' => $amount - $platformFee - $processingFee,
                'processing_fee' => $processingFee,
            ];
        });
    }
}
