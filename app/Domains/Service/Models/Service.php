<?php

namespace App\Domains\Service\Models;

use App\Domains\Media\Models\Media;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use Database\Factories\ServiceFactory;
use App\Domains\Booking\Models\Booking;
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
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, HasCategories, HasMedia, HasSettings, HasUuid, HasVideoEmbeds, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'is_active',
        'sort_order',
        'settings',
    ];

    // Note: cover is not in $appends - use ServiceResource->withMedia() when needed

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'settings' => 'array',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ServiceFactory
    {
        return ServiceFactory::new();
    }

    /**
     * Check if service uses provider defaults for booking settings.
     */
    public function usesProviderDefaults(): bool
    {
        return $this->getSetting('use_provider_defaults', true);
    }

    /**
     * Get effective booking settings (from provider if using defaults, otherwise from service).
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
            'buffer_minutes' => $this->getSetting('buffer_minutes'),
        ];
    }

    /**
     * Get the effective buffer minutes between bookings.
     * Service buffer overrides provider buffer if set.
     */
    public function getEffectiveBufferMinutes(): int
    {
        // Service-level buffer takes precedence if set
        $bufferMinutes = $this->getSetting('buffer_minutes');
        if ($bufferMinutes !== null) {
            return (int) $bufferMinutes;
        }

        // Fall back to provider buffer
        return $this->provider->getBufferMinutes();
    }

    /**
     * Get buffer minutes for this service.
     */
    public function getBufferMinutes(): ?int
    {
        $buffer = $this->getSetting('buffer_minutes');

        return $buffer !== null ? (int) $buffer : null;
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the team members assigned to this service.
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class, 'service_team_member')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope to filter services by category IDs (using polymorphic relationship).
     */
    public function scopeInCategories($query, array $categoryIds)
    {
        return $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        });
    }

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

    public function getPriceDisplayAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get the cover image media object.
     */
    public function getCoverAttribute(): ?Media
    {
        return $this->getFirstMedia('cover');
    }

    /**
     * Get the cover image URL.
     */
    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover?->medium;
    }

    /**
     * Get the cover image thumbnail URL.
     */
    public function getCoverThumbnailAttribute(): ?string
    {
        return $this->cover?->thumbnail;
    }

    /**
     * Get the display image URL (alias for cover_url for backwards compatibility).
     */
    public function getDisplayImageUrlAttribute(): ?string
    {
        return $this->cover_url;
    }
}
