<?php

namespace App\Domains\Booking\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Context flags for conditional inclusion.
     */
    protected bool $includeClient = true;

    protected bool $includeProvider = false;

    protected bool $includeService = true;

    protected bool $includePayment = false;

    protected bool $includeReview = false;

    /**
     * Include client information.
     */
    public function withClient(bool $include = true): self
    {
        $this->includeClient = $include;

        return $this;
    }

    /**
     * Include provider information.
     */
    public function withProvider(bool $include = true): self
    {
        $this->includeProvider = $include;

        return $this;
    }

    /**
     * Include service information.
     */
    public function withService(bool $include = true): self
    {
        $this->includeService = $include;

        return $this;
    }

    /**
     * Include payment information.
     */
    public function withPayment(bool $include = true): self
    {
        $this->includePayment = $include;

        return $this;
    }

    /**
     * Include review information.
     */
    public function withReview(bool $include = true): self
    {
        $this->includeReview = $include;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,

            // Date/time fields (all snake_case)
            'booking_date' => $this->booking_date->format('Y-m-d'),
            'formatted_date' => $this->formatted_date,
            'formatted_time' => $this->formatted_time,
            'start_time' => $this->start_time?->format('g:i A'),
            'end_time' => $this->end_time?->format('g:i A'),

            // Status with pre-computed display values
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'status_icon' => $this->status->icon(),

            // Amounts
            'service_price' => (float) $this->service_price,
            'platform_fee' => (float) ($this->platform_fee ?? 0),
            'total_amount' => (float) $this->total_amount,
            'total_display' => $this->total_display,

            // Notes
            'client_notes' => $this->client_notes,
            'provider_notes' => $this->provider_notes,
            'cancellation_reason' => $this->cancellation_reason,

            // Computed state booleans
            'is_past' => $this->isPast(),
            'is_today' => $this->isToday(),
            'is_guest_booking' => $this->isGuestBooking(),

            // Action permissions
            'can_confirm' => $this->canBeConfirmed(),
            'can_complete' => $this->canBeCompleted(),
            'can_cancel' => $this->canBeCancelled(),
            'can_review' => $this->canBeReviewed(),

            // Deposit/payment tracking
            'requires_deposit' => $this->requiresDeposit(),
            'deposit_amount' => (float) ($this->deposit_amount ?? 0),
            'deposit_paid' => $this->isDepositPaid(),
            'balance_amount' => $this->balance_amount,
            'can_pay' => $this->canProceedToPayment(),

            // Fee breakdown (new structure)
            'zeen_fee' => (float) ($this->zeen_fee ?? 0),
            'gateway_fee' => (float) ($this->gateway_fee ?? 0),
            'convenience_fee' => (float) ($this->convenience_fee ?? 0),
            'fee_payer' => $this->fee_payer ?? 'provider',

            // Timestamps
            'confirmed_at' => $this->confirmed_at?->format('M j, Y g:i A'),
            'completed_at' => $this->completed_at?->format('M j, Y g:i A'),
            'cancelled_at' => $this->cancelled_at?->format('M j, Y g:i A'),
            'created_at' => $this->created_at->format('M j, Y g:i A'),
        ];

        // Conditional nested resources
        if ($this->includeClient) {
            $data['client'] = $this->formatClient();
        }

        if ($this->includeProvider) {
            $data['provider'] = $this->formatProvider();
        }

        if ($this->includeService) {
            $data['service'] = $this->formatService();
        }

        if ($this->includePayment && $this->relationLoaded('payment') && $this->payment) {
            $data['payment'] = $this->formatPayment();
        }

        if ($this->includeReview && $this->relationLoaded('review') && $this->review) {
            $data['review'] = $this->formatReview();
        }

        return $data;
    }

    /**
     * Format client information.
     * Handles both guest and registered users.
     */
    protected function formatClient(): array
    {
        if ($this->isGuestBooking()) {
            return [
                'name' => $this->guest_name,
                'email' => $this->guest_email,
                'phone' => $this->guest_phone,
                'avatar' => null,
                'is_guest' => true,
            ];
        }

        return [
            'name' => $this->client?->name,
            'email' => $this->client?->email,
            'phone' => $this->client?->phone,
            'avatar' => $this->client?->avatar,
            'is_guest' => false,
        ];
    }

    /**
     * Format provider information.
     */
    protected function formatProvider(): array
    {
        return [
            'id' => $this->provider->id,
            'uuid' => $this->provider->uuid,
            'business_name' => $this->provider->business_name,
            'slug' => $this->provider->slug,
            'avatar' => $this->provider->user?->avatar,
            'address' => $this->provider->address,
            'domain' => $this->provider->domain,
        ];
    }

    /**
     * Format service information.
     */
    protected function formatService(): array
    {
        return [
            'id' => $this->service->id,
            'uuid' => $this->service->uuid,
            'name' => $this->service->name,
            'description' => $this->service->description,
            'duration_minutes' => $this->service->duration_minutes,
            'price' => (float) $this->service->price,
            'price_display' => $this->service->price_display,
        ];
    }

    /**
     * Format payment information.
     */
    protected function formatPayment(): ?array
    {
        if (! $this->payment) {
            return null;
        }

        return [
            'id' => $this->payment->id,
            'uuid' => $this->payment->uuid,
            'amount' => (float) $this->payment->amount,
            'amount_display' => '$'.number_format($this->payment->amount, 2),
            'status' => $this->payment->status->value,
            'status_label' => $this->payment->status->label(),
            'status_color' => $this->payment->status->color(),
            'payment_type' => $this->payment->payment_type ?? 'full',
            'card_display' => $this->payment->card_display ?? null,
            'paid_at' => $this->payment->paid_at?->format('M j, Y g:i A'),
        ];
    }

    /**
     * Format review information.
     */
    protected function formatReview(): ?array
    {
        if (! $this->review) {
            return null;
        }

        return [
            'id' => $this->review->id,
            'uuid' => $this->review->uuid,
            'rating' => $this->review->rating,
            'comment' => $this->review->comment,
            'provider_response' => $this->review->provider_response,
            'created_at' => $this->review->created_at->format('M j, Y'),
        ];
    }
}
