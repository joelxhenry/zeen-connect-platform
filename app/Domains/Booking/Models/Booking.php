<?php

namespace App\Domains\Booking\Models;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Provider\Models\Provider;
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
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'service_price' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => BookingStatus::class,
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
}
