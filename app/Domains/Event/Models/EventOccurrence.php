<?php

namespace App\Domains\Event\Models;

use App\Domains\Event\Enums\OccurrenceStatus;
use App\Support\Traits\HasUuid;
use Database\Factories\EventOccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventOccurrence extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'event_id',
        'start_datetime',
        'end_datetime',
        'capacity_override',
        'spots_remaining',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_datetime' => 'datetime',
            'end_datetime' => 'datetime',
            'capacity_override' => 'integer',
            'spots_remaining' => 'integer',
            'status' => OccurrenceStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EventOccurrenceFactory
    {
        return EventOccurrenceFactory::new();
    }

    /**
     * Get the event this occurrence belongs to.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all bookings for this occurrence.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    /**
     * Get the effective capacity for this occurrence.
     */
    public function getCapacity(): int
    {
        if ($this->capacity_override !== null) {
            return $this->capacity_override;
        }

        return $this->event->capacity ?? PHP_INT_MAX;
    }

    /**
     * Get the number of spots remaining.
     */
    public function getSpotsRemaining(): int
    {
        return $this->spots_remaining;
    }

    /**
     * Check if there are spots available.
     */
    public function hasAvailability(): bool
    {
        return $this->spots_remaining > 0;
    }

    /**
     * Check if the occurrence is fully booked.
     */
    public function isFull(): bool
    {
        return $this->spots_remaining <= 0;
    }

    /**
     * Check if this occurrence is in the past.
     */
    public function isPast(): bool
    {
        return $this->start_datetime->isPast();
    }

    /**
     * Check if this occurrence is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_datetime->isFuture();
    }

    /**
     * Check if this occurrence is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === OccurrenceStatus::SCHEDULED;
    }

    /**
     * Check if this occurrence is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === OccurrenceStatus::CANCELLED;
    }

    /**
     * Check if this occurrence is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === OccurrenceStatus::COMPLETED;
    }

    /**
     * Check if this occurrence can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->isScheduled() && $this->isUpcoming();
    }

    /**
     * Decrement spots remaining when a booking is made.
     */
    public function decrementSpots(int $spots = 1): void
    {
        $this->decrement('spots_remaining', $spots);
    }

    /**
     * Increment spots remaining when a booking is cancelled.
     */
    public function incrementSpots(int $spots = 1): void
    {
        $this->increment('spots_remaining', $spots);
    }

    /**
     * Get the confirmed bookings count.
     */
    public function getConfirmedBookingsCount(): int
    {
        return $this->bookings()
            ->whereIn('status', ['confirmed', 'attended'])
            ->sum('spots_booked');
    }

    /**
     * Get the date display format.
     */
    public function getDateDisplayAttribute(): string
    {
        return $this->start_datetime->format('M j, Y');
    }

    /**
     * Get the time display format.
     */
    public function getTimeDisplayAttribute(): string
    {
        return $this->start_datetime->format('g:i A').' - '.$this->end_datetime->format('g:i A');
    }

    /**
     * Get the full datetime display format.
     */
    public function getDatetimeDisplayAttribute(): string
    {
        return $this->start_datetime->format('M j, Y \\a\\t g:i A');
    }

    /**
     * Scope to filter only scheduled occurrences.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', OccurrenceStatus::SCHEDULED);
    }

    /**
     * Scope to filter only cancelled occurrences.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', OccurrenceStatus::CANCELLED);
    }

    /**
     * Scope to filter only completed occurrences.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', OccurrenceStatus::COMPLETED);
    }

    /**
     * Scope to filter upcoming occurrences.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_datetime', '>=', now());
    }

    /**
     * Scope to filter past occurrences.
     */
    public function scopePast($query)
    {
        return $query->where('start_datetime', '<', now());
    }

    /**
     * Scope to filter occurrences within a date range.
     */
    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('start_datetime', [$start, $end]);
    }
}
