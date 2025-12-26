<?php

namespace App\Domains\Provider\Models;

use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\Subscription;
use App\Models\User;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, HasSlug, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'bio',
        'tagline',
        'address',
        'website',
        'social_links',
        'status',
        'commission_rate',
        'is_featured',
        'requires_approval',
        'deposit_type',
        'deposit_amount',
        'cancellation_policy',
        'advance_booking_days',
        'min_booking_notice_hours',
        'processing_fee_payer',
        'deposit_percentage',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'commission_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'is_featured' => 'boolean',
            'verified_at' => 'datetime',
            'requires_approval' => 'boolean',
            'deposit_amount' => 'decimal:2',
            'advance_booking_days' => 'integer',
            'min_booking_notice_hours' => 'integer',
            'deposit_percentage' => 'decimal:2',
        ];
    }

    /**
     * Get booking settings as an array.
     */
    public function getBookingSettings(): array
    {
        return [
            'requires_approval' => $this->requires_approval ?? false,
            'deposit_type' => $this->deposit_type ?? 'none',
            'deposit_amount' => $this->deposit_amount,
            'cancellation_policy' => $this->cancellation_policy ?? 'flexible',
            'advance_booking_days' => $this->advance_booking_days ?? 30,
            'min_booking_notice_hours' => $this->min_booking_notice_hours ?? 24,
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($provider) {
            if (empty($provider->slug)) {
                $provider->slug = static::generateSlug($provider->business_name);
            }
        });

        static::updating(function ($provider) {
            if ($provider->isDirty('business_name') && ! $provider->isDirty('slug')) {
                $provider->slug = static::generateSlug($provider->business_name, $provider->id);
            }
        });
    }

    /**
     * Get the user that owns this provider profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all services offered by this provider.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get the provider's availability schedule.
     */
    public function availability(): HasMany
    {
        return $this->hasMany(ProviderAvailability::class);
    }

    /**
     * Get the provider's blocked dates.
     */
    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    /**
     * Get the provider's reviews.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get visible reviews only.
     */
    public function visibleReviews(): HasMany
    {
        return $this->reviews()->visible();
    }

    /**
     * Get the provider's active subscription.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    /**
     * Get all subscriptions for this provider.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the provider's subscription tier.
     */
    public function getTier(): SubscriptionTier
    {
        return $this->subscription?->tier ?? SubscriptionTier::FREE;
    }

    /**
     * Check if provider is on free tier.
     */
    public function isFreeTier(): bool
    {
        return $this->getTier() === SubscriptionTier::FREE;
    }

    /**
     * Check if provider is on premium tier.
     */
    public function isPremiumTier(): bool
    {
        return $this->getTier() === SubscriptionTier::PREMIUM;
    }

    /**
     * Check if provider is on enterprise tier.
     */
    public function isEnterpriseTier(): bool
    {
        return $this->getTier() === SubscriptionTier::ENTERPRISE;
    }

    /**
     * Check if the provider is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the provider is pending verification.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the provider is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Scope a query to only include active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->whereHas('user', fn ($q) => $q->where('is_active', true));
    }

    /**
     * Scope a query to only include featured providers.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the public URL for this provider's profile.
     */
    public function getPublicUrlAttribute(): string
    {
        return route('provider.public', $this->slug);
    }

    /**
     * Update rating statistics.
     */
    public function updateRatingStats(): void
    {
        $stats = $this->visibleReviews()
            ->selectRaw('AVG(rating) as avg, COUNT(*) as count')
            ->first();

        $this->update([
            'rating_avg' => round($stats->avg ?? 0, 2),
            'rating_count' => $stats->count ?? 0,
        ]);
    }

    /**
     * Get the rating display string.
     */
    public function getRatingDisplayAttribute(): string
    {
        if ($this->rating_count === 0) {
            return 'New';
        }

        return number_format($this->rating_avg, 1);
    }

    /**
     * Get the reviews summary text.
     */
    public function getReviewsSummaryAttribute(): string
    {
        if ($this->rating_count === 0) {
            return 'No reviews yet';
        }

        $reviewWord = $this->rating_count === 1 ? 'review' : 'reviews';

        return "{$this->rating_display} ({$this->rating_count} {$reviewWord})";
    }

    /**
     * Get users who have favorited this provider.
     */
    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'favorites',
            'provider_id',
            'user_id'
        )->withTimestamps();
    }

    /**
     * Get the number of users who have favorited this provider.
     */
    public function getFavoritesCountAttribute(): int
    {
        return $this->favoritedBy()->count();
    }
}
