<?php

namespace App\Domains\Payment\Resources;

use App\Domains\Shared\Resources\Concerns\HasDisplayValues;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    use HasDisplayValues;

    /**
     * Context flags for conditional inclusion.
     */
    protected bool $includeClient = false;

    protected bool $includeProvider = false;

    protected bool $includeBooking = false;

    protected bool $includeGatewayDetails = false;

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
     * Include booking information.
     */
    public function withBooking(bool $include = true): self
    {
        $this->includeBooking = $include;

        return $this;
    }

    /**
     * Include gateway details (for admin view).
     */
    public function withGatewayDetails(bool $include = true): self
    {
        $this->includeGatewayDetails = $include;

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
            'booking_uuid' => $this->booking?->uuid,
            'client_name' => $this->client?->name ?? $this->booking?->guest_name ?? 'Guest',
            'service_name' => $this->booking?->service?->name ?? 'Service',
            'booking_date' => $this->booking?->booking_date?->format('M j, Y'),

            // Amounts with display values
            'amount' => (float) $this->amount,
            'amount_display' => $this->amount_display,
            'platform_fee' => (float) ($this->platform_fee ?? 0),
            'platform_fee_display' => $this->platform_fee_display,
            'provider_amount' => (float) ($this->provider_amount ?? 0),
            'provider_amount_display' => $this->provider_amount_display,
            'processing_fee' => (float) ($this->processing_fee ?? 0),
            'currency' => $this->currency ?? 'USD',

            // Payment type and method
            'payment_type' => $this->payment_type ?? 'full',
            'gateway' => $this->gateway,
            'card_display' => $this->card_display,

            // Status with pre-computed display values
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'status_icon' => $this->status->icon(),

            // State booleans
            'is_completed' => $this->isCompleted(),
            'is_pending' => $this->isPending(),
            'is_failed' => $this->isFailed(),
            'can_refund' => $this->canBeRefunded(),
            'is_refunded' => $this->is_refunded ?? false,

            // Failure info
            'failure_reason' => $this->failure_reason,

            // Refund details
            'refund_reason' => $this->refund_reason,
            'refund_transaction_id' => $this->refund_transaction_id,

            // Timestamps
            'paid_at' => $this->formatDateTime($this->paid_at),
            'refunded_at' => $this->formatDateTime($this->refunded_at),
            'created_at' => $this->formatDateTime($this->created_at),
        ];

        // Conditional nested resources
        if ($this->includeClient && $this->relationLoaded('client')) {
            $data['client'] = $this->formatClient();
        }

        if ($this->includeProvider && $this->relationLoaded('provider')) {
            $data['provider'] = $this->formatProvider();
        }

        if ($this->includeBooking && $this->relationLoaded('booking')) {
            $data['booking'] = $this->formatBooking();
        }

        if ($this->includeGatewayDetails) {
            $data['gateway_type'] = $this->gateway_type;
            $data['gateway_provider'] = $this->gateway_provider;
            $data['gateway_transaction_id'] = $this->gateway_transaction_id;
            $data['gateway_order_id'] = $this->gateway_order_id;
            $data['gateway_response_code'] = $this->gateway_response_code;
            $data['processing_fee_payer'] = $this->processing_fee_payer;
        }

        return $data;
    }

    /**
     * Format client information.
     */
    protected function formatClient(): ?array
    {
        if (! $this->client) {
            return null;
        }

        return [
            'id' => $this->client->id,
            'name' => $this->client->name,
            'email' => $this->client->email,
            'phone' => $this->client->phone ?? null,
        ];
    }

    /**
     * Format provider information.
     */
    protected function formatProvider(): ?array
    {
        if (! $this->provider) {
            return null;
        }

        return [
            'id' => $this->provider->id,
            'uuid' => $this->provider->uuid,
            'business_name' => $this->provider->business_name,
            'slug' => $this->provider->slug,
        ];
    }

    /**
     * Format booking information.
     */
    protected function formatBooking(): ?array
    {
        if (! $this->booking) {
            return null;
        }

        return [
            'id' => $this->booking->id,
            'uuid' => $this->booking->uuid,
            'booking_date' => $this->booking->booking_date?->format('Y-m-d'),
            'formatted_date' => $this->booking->formatted_date ?? $this->booking->booking_date?->format('M j, Y'),
            'start_time' => $this->booking->start_time?->format('g:i A'),
            'end_time' => $this->booking->end_time?->format('g:i A'),
            'status' => $this->booking->status?->value,
            'service_name' => $this->booking->service?->name,
            'service_duration' => $this->booking->service?->duration_minutes,
            'guest_name' => $this->booking->guest_name,
            'guest_email' => $this->booking->guest_email,
        ];
    }
}
