<?php

namespace App\Domains\Event\Models;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventStatus;
use App\Domains\Event\Enums\EventType;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Service\Traits\HasCategories;
use App\Support\Traits\HasSettings;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, HasCategories, HasMedia, HasSettings, HasUuid, HasVideoEmbeds, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'event_type',
        'location_type',
        'location',
        'virtual_meeting_url',
        'duration_minutes',
        'capacity',
        'price',
        'settings',
        'status',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'location_type' => EventLocationType::class,
            'status' => EventStatus::class,
            'duration_minutes' => 'integer',
            'capacity' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * Get the provider that owns this event.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get all occurrences for this event.
     */
    public function occurrences(): HasMany
    {
        return $this->hasMany(EventOccurrence::class);
    }

    /**
     * Get the recurrence rule for this event.
     */
    public function recurrenceRule(): HasOne
    {
        return $this->hasOne(EventRecurrenceRule::class);
    }

    /**
     * Get all bookings for this event through occurrences.
     */
    public function bookings(): HasManyThrough
    {
        return $this->hasManyThrough(
            EventBooking::class,
            EventOccurrence::class,
            'event_id',
            'event_occurrence_id'
        );
    }

    /**
     * Get the team members assigned to this event.
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class, 'event_team_member')
            ->withTimestamps();
    }

    /**
     * Check if this is a recurring event.
     */
    public function isRecurring(): bool
    {
        return $this->event_type === EventType::RECURRING;
    }

    /**
     * Check if this is a one-time event.
     */
    public function isOneTime(): bool
    {
        return $this->event_type === EventType::ONE_TIME;
    }

    /**
     * Check if this event is published.
     */
    public function isPublished(): bool
    {
        return $this->status === EventStatus::PUBLISHED;
    }

    /**
     * Check if this event is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === EventStatus::DRAFT;
    }

    /**
     * Check if this event is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === EventStatus::CANCELLED;
    }

    /**
     * Check if this event is virtual.
     */
    public function isVirtual(): bool
    {
        return $this->location_type === EventLocationType::VIRTUAL;
    }

    /**
     * Check if this event is in-person.
     */
    public function isInPerson(): bool
    {
        return $this->location_type === EventLocationType::IN_PERSON;
    }

    /**
     * Check if this event has unlimited capacity.
     */
    public function hasUnlimitedCapacity(): bool
    {
        return $this->capacity === null;
    }

    /**
     * Check if the event can be edited.
     */
    public function canBeEdited(): bool
    {
        return ! $this->isCancelled();
    }

    /**
     * Get total bookings count across all occurrences.
     */
    public function getTotalBookingsCount(): int
    {
        return $this->bookings()->count();
    }

    /**
     * Get the next upcoming occurrence.
     */
    public function getNextOccurrence(): ?EventOccurrence
    {
        return $this->occurrences()
            ->scheduled()
            ->upcoming()
            ->orderBy('start_datetime')
            ->first();
    }

    /**
     * Check if event uses provider defaults for booking settings.
     */
    public function usesProviderDefaults(): bool
    {
        return $this->getSetting('use_provider_defaults', true);
    }

    /**
     * Get effective booking settings (from provider if using defaults, otherwise from event).
     */
    public function getEffectiveBookingSettings(): array
    {
        if ($this->usesProviderDefaults()) {
            return $this->provider->getBookingSettings();
        }

        return [
            'requires_approval' => $this->getSetting('requires_approval', false),
            'deposit_type' => $this->getSetting('deposit_type', 'none'),
            'deposit_amount' => $this->getSetting('deposit_amount'),
            'cancellation_policy' => $this->getSetting('cancellation_policy', 'flexible'),
            'advance_booking_days' => $this->getSetting('advance_booking_days', 30),
            'min_booking_notice_hours' => $this->getSetting('min_booking_notice_hours', 24),
        ];
    }

    /**
     * Get the duration in display format.
     */
    public function getDurationDisplayAttribute(): string
    {
        $hours = intdiv($this->duration_minutes, 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}m";
    }

    /**
     * Get the price in display format.
     */
    public function getPriceDisplayAttribute(): string
    {
        if ((float) $this->price === 0.0) {
            return 'Free';
        }

        return '$'.number_format($this->price, 2);
    }

    /**
     * Get the location display value.
     */
    public function getLocationDisplayAttribute(): string
    {
        if ($this->isVirtual()) {
            return 'Virtual Event';
        }

        return $this->location ?? 'Location TBD';
    }

    /**
     * Get the capacity display value.
     */
    public function getCapacityDisplayAttribute(): string
    {
        if ($this->hasUnlimitedCapacity()) {
            return 'Unlimited';
        }

        return (string) $this->capacity;
    }

    /**
     * Scope to filter only active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter only published events.
     */
    public function scopePublished($query)
    {
        return $query->where('status', EventStatus::PUBLISHED);
    }

    /**
     * Scope to filter only draft events.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', EventStatus::DRAFT);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope to filter recurring events.
     */
    public function scopeRecurring($query)
    {
        return $query->where('event_type', EventType::RECURRING);
    }

    /**
     * Scope to filter one-time events.
     */
    public function scopeOneTime($query)
    {
        return $query->where('event_type', EventType::ONE_TIME);
    }

    /**
     * Scope to filter events with upcoming occurrences.
     */
    public function scopeUpcoming($query)
    {
        return $query->whereHas('occurrences', function ($q) {
            $q->scheduled()->where('start_datetime', '>=', now());
        });
    }

    /**
     * Scope to order by sort order and name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
