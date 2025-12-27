<?php

namespace App\Domains\Service\Models;

use App\Domains\Media\Models\Media;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use App\Domains\Provider\Models\Provider;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'display_media_id',
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
     * Get the display image for this service.
     */
    public function displayMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'display_media_id');
    }

    /**
     * Get the display image URL, falling back to first gallery image.
     */
    public function getDisplayImageUrlAttribute(): ?string
    {
        // First check if there's a designated display image
        if ($this->display_media_id && $this->displayMedia) {
            return $this->displayMedia->medium;
        }

        // Fall back to first gallery image
        $firstGalleryImage = $this->getFirstMedia('gallery');
        return $firstGalleryImage?->medium;
    }

    /**
     * Get the display image thumbnail URL.
     */
    public function getDisplayImageThumbnailAttribute(): ?string
    {
        if ($this->display_media_id && $this->displayMedia) {
            return $this->displayMedia->thumbnail;
        }

        $firstGalleryImage = $this->getFirstMedia('gallery');
        return $firstGalleryImage?->thumbnail;
    }

    /**
     * Set the display image from a media ID.
     */
    public function setDisplayImage(?int $mediaId): void
    {
        // Verify the media belongs to this service
        if ($mediaId) {
            $media = $this->media()->where('id', $mediaId)->first();
            if (!$media) {
                return;
            }
        }

        $this->update(['display_media_id' => $mediaId]);
    }
}
