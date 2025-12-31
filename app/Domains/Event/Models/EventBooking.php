<?php

namespace App\Domains\Event\Models;

use App\Domains\Event\Enums\EventBookingStatus;
use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBooking extends Model
{
    use HasUuid;

    protected $fillable = [
        'event_occurrence_id',
        'client_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'spots_booked',
        'total_amount',
        'deposit_amount',
        'deposit_paid',
        'status',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'client_notes',
        'provider_notes',
    ];

    protected function casts(): array
    {
        return [
            'spots_booked' => 'integer',
            'total_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'deposit_paid' => 'boolean',
            'status' => EventBookingStatus::class,
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the occurrence this booking is for.
     */
    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(EventOccurrence::class, 'event_occurrence_id');
    }

    /**
     * Get the client who made the booking.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Check if this is a guest booking.
     */
    public function isGuestBooking(): bool
    {
        return $this->client_id === null;
    }

    /**
     * Check if this is an authenticated user booking.
     */
    public function isAuthenticatedBooking(): bool
    {
        return $this->client_id !== null;
    }

    /**
     * Get the booker's name.
     */
    public function getBookerNameAttribute(): string
    {
        if ($this->isAuthenticatedBooking()) {
            return $this->client->name ?? 'Unknown';
        }

        return $this->guest_name ?? 'Guest';
    }

    /**
     * Get the booker's email.
     */
    public function getBookerEmailAttribute(): ?string
    {
        if ($this->isAuthenticatedBooking()) {
            return $this->client->email;
        }

        return $this->guest_email;
    }

    /**
     * Get the booker's phone.
     */
    public function getBookerPhoneAttribute(): ?string
    {
        if ($this->isAuthenticatedBooking()) {
            return $this->client->phone ?? null;
        }

        return $this->guest_phone;
    }

    /**
     * Check if booking is pending.
     */
    public function isPending(): bool
    {
        return $this->status === EventBookingStatus::PENDING;
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === EventBookingStatus::CONFIRMED;
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === EventBookingStatus::CANCELLED;
    }

    /**
     * Check if attendee attended.
     */
    public function hasAttended(): bool
    {
        return $this->status === EventBookingStatus::ATTENDED;
    }

    /**
     * Check if attendee was a no-show.
     */
    public function isNoShow(): bool
    {
        return $this->status === EventBookingStatus::NO_SHOW;
    }

    /**
     * Check if deposit is required.
     */
    public function requiresDeposit(): bool
    {
        return $this->deposit_amount !== null && $this->deposit_amount > 0;
    }

    /**
     * Check if deposit has been paid.
     */
    public function isDepositPaid(): bool
    {
        return $this->deposit_paid;
    }

    /**
     * Get the amount due (total minus deposit if paid).
     */
    public function getAmountDueAttribute(): float
    {
        if ($this->deposit_paid && $this->deposit_amount) {
            return (float) $this->total_amount - (float) $this->deposit_amount;
        }

        return (float) $this->total_amount;
    }

    /**
     * Get the total amount display format.
     */
    public function getTotalAmountDisplayAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2);
    }

    /**
     * Get the deposit amount display format.
     */
    public function getDepositAmountDisplayAttribute(): ?string
    {
        if (! $this->requiresDeposit()) {
            return null;
        }

        return '$'.number_format($this->deposit_amount, 2);
    }

    /**
     * Confirm this booking.
     */
    public function confirm(): void
    {
        $this->status = EventBookingStatus::CONFIRMED;
        $this->confirmed_at = now();
        $this->save();
    }

    /**
     * Cancel this booking.
     */
    public function cancel(?string $reason = null): void
    {
        $this->status = EventBookingStatus::CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->save();

        // Return spots to the occurrence
        $this->occurrence->incrementSpots($this->spots_booked);
    }

    /**
     * Mark as attended.
     */
    public function markAsAttended(): void
    {
        $this->status = EventBookingStatus::ATTENDED;
        $this->save();
    }

    /**
     * Mark as no-show.
     */
    public function markAsNoShow(): void
    {
        $this->status = EventBookingStatus::NO_SHOW;
        $this->save();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, EventBookingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('status', EventBookingStatus::PENDING);
    }

    /**
     * Scope to filter confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', EventBookingStatus::CONFIRMED);
    }

    /**
     * Scope to filter cancelled bookings.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', EventBookingStatus::CANCELLED);
    }

    /**
     * Scope to filter active bookings (not cancelled).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [
            EventBookingStatus::CANCELLED,
        ]);
    }
}
