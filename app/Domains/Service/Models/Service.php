<?php

namespace App\Domains\Service\Models;

use App\Domains\Media\Models\Media;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Models\Provider;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, HasMedia, HasUuid, HasVideoEmbeds, SoftDeletes;

    protected $fillable = [
        'provider_id',
        'category_id',
        'name',
        'description',
        'duration_minutes',
        'price',
        'is_active',
        'sort_order',
        'use_provider_defaults',
        'requires_approval',
        'deposit_type',
        'deposit_amount',
        'cancellation_policy',
        'advance_booking_days',
        'min_booking_notice_hours',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'use_provider_defaults' => 'boolean',
            'requires_approval' => 'boolean',
            'deposit_amount' => 'decimal:2',
            'advance_booking_days' => 'integer',
            'min_booking_notice_hours' => 'integer',
        ];
    }

    /**
     * Get effective booking settings (from provider if using defaults, otherwise from service).
     */
    public function getEffectiveBookingSettings(): array
    {
        if ($this->use_provider_defaults) {
            return $this->provider->getBookingSettings();
        }

        return [
            'requires_approval' => $this->requires_approval ?? false,
            'deposit_type' => $this->deposit_type ?? 'none',
            'deposit_amount' => $this->deposit_amount,
            'cancellation_policy' => $this->cancellation_policy ?? 'flexible',
            'advance_booking_days' => $this->advance_booking_days ?? 30,
            'min_booking_notice_hours' => $this->min_booking_notice_hours ?? 24,
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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

    public function scopeInCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
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
