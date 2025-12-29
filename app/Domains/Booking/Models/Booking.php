<?php

namespace App\Domains\Booking\Models;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Payment\Models\Payment;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'client_id',
        'provider_id',
        'team_member_id',
        'service_id',
        'booking_date',
        'start_time',
        'end_time',
        'status',
        'service_price',
        'platform_fee',
        'total_amount',
        'client_notes',
        'provider_notes',
        'cancellation_reason',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        // Guest booking fields
        'guest_email',
        'guest_name',
        'guest_phone',
        // Deposit tracking
        'deposit_amount',
        'deposit_paid',
        // Fee tracking (legacy)
        'platform_fee_amount',
        'processing_fee_amount',
        // New separated fee structure
        'zeen_fee',
        'gateway_fee',
        'convenience_fee',
        'fee_payer',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'service_price' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => BookingStatus::class,
            'deposit_amount' => 'decimal:2',
            'deposit_paid' => 'boolean',
            'platform_fee_amount' => 'decimal:2',
            'processing_fee_amount' => 'decimal:2',
            // New fee columns
            'zeen_fee' => 'decimal:2',
            'gateway_fee' => 'decimal:2',
            'convenience_fee' => 'decimal:2',
        ];
    }

    /**
     * Get the client (user) who made the booking.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Get the provider for this booking.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get the service being booked.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the team member assigned to this booking.
     */
    public function teamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class);
    }

    /**
     * Check if this booking is assigned to a team member.
     */
    public function hasTeamMember(): bool
    {
        return ! is_null($this->team_member_id);
    }

    /**
     * Get the payment for this booking.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Get all payments for this booking.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the review for this booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Check if booking has a review.
     */
    public function hasReview(): bool
    {
        return $this->review()->exists();
    }

    /**
     * Check if booking can be reviewed.
     */
    public function canBeReviewed(): bool
    {
        return $this->status === BookingStatus::COMPLETED && ! $this->hasReview();
    }

    /**
     * Scope to filter by status.
     */
    public function scopeStatus($query, BookingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get upcoming bookings (confirmed, future date).
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', BookingStatus::CONFIRMED)
            ->where(function ($q) {
                $q->where('booking_date', '>', now()->toDateString())
                    ->orWhere(function ($q2) {
                        $q2->where('booking_date', now()->toDateString())
                            ->where('start_time', '>', now()->format('H:i:s'));
                    });
            })
            ->orderBy('booking_date')
            ->orderBy('start_time');
    }

    /**
     * Scope to get past bookings.
     */
    public function scopePast($query)
    {
        return $query->where(function ($q) {
            $q->where('booking_date', '<', now()->toDateString())
                ->orWhere(function ($q2) {
                    $q2->where('booking_date', now()->toDateString())
                        ->where('end_time', '<', now()->format('H:i:s'));
                });
        })
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time');
    }

    /**
     * Scope for bookings on a specific date.
     */
    public function scopeOnDate($query, string $date)
    {
        return $query->where('booking_date', $date);
    }

    /**
     * Scope for a provider's bookings.
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope for a client's bookings.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for a team member's bookings.
     */
    public function scopeForTeamMember($query, int $teamMemberId)
    {
        return $query->where('team_member_id', $teamMemberId);
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canTransitionTo(BookingStatus::CANCELLED);
    }

    /**
     * Check if booking can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->status->canTransitionTo(BookingStatus::CONFIRMED);
    }

    /**
     * Check if booking can be completed.
     */
    public function canBeCompleted(): bool
    {
        return $this->status->canTransitionTo(BookingStatus::COMPLETED);
    }

    /**
     * Get formatted date display.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->booking_date->format('D, M j, Y');
    }

    /**
     * Get formatted time range.
     */
    public function getFormattedTimeAttribute(): string
    {
        $start = date('g:i A', strtotime($this->start_time));
        $end = date('g:i A', strtotime($this->end_time));

        return "{$start} - {$end}";
    }

    /**
     * Get formatted total amount.
     */
    public function getTotalDisplayAttribute(): string
    {
        return '$'.number_format($this->total_amount, 2);
    }


    public function getClientName()
    {
        return $this->isGuestBooking() ? $this->guest_name : $this->client?->name;
    }


    public function getClientEmail()
    {
        return $this->isGuestBooking() ? $this->guest_email : $this->client?->email;
    }

    /**
     * Check if booking is in the past.
     */
    public function isPast(): bool
    {
        if ($this->booking_date->lt(now()->startOfDay())) {
            return true;
        }

        if ($this->booking_date->eq(now()->startOfDay())) {
            return $this->end_time < now()->format('H:i:s');
        }

        return false;
    }

    /**
     * Check if booking is today.
     */
    public function isToday(): bool
    {
        return $this->booking_date->isToday();
    }

    /**
     * Check if this is a guest booking (no registered user).
     */
    public function isGuestBooking(): bool
    {
        return is_null($this->client_id);
    }

    /**
     * Check if booking requires a deposit.
     */
    public function requiresDeposit(): bool
    {
        return $this->deposit_amount > 0;
    }

    /**
     * Check if the deposit has been paid.
     */
    public function isDepositPaid(): bool
    {
        return $this->deposit_paid ?? false;
    }

    /**
     * Get the client email (works for both guest and registered users).
     */
    public function getClientEmailAttribute(): ?string
    {
        if ($this->isGuestBooking()) {
            return $this->guest_email;
        }

        return $this->client?->email;
    }

    /**
     * Get the client name (works for both guest and registered users).
     */
    public function getClientNameAttribute(): ?string
    {
        if ($this->isGuestBooking()) {
            return $this->guest_name;
        }

        return $this->client?->name;
    }

    /**
     * Get the client phone (works for both guest and registered users).
     */
    public function getClientPhoneAttribute(): ?string
    {
        if ($this->isGuestBooking()) {
            return $this->guest_phone;
        }

        return $this->client?->phone;
    }

    /**
     * Check if booking can proceed to payment.
     */
    public function canProceedToPayment(): bool
    {
        return $this->requiresDeposit() && ! $this->isDepositPaid();
    }

    /**
     * Get the balance remaining after deposit.
     */
    public function getBalanceAmountAttribute(): float
    {
        if (! $this->requiresDeposit()) {
            return (float) $this->total_amount;
        }

        return (float) ($this->total_amount - $this->deposit_amount);
    }
}
