<?php

namespace App\Domains\Provider\Models;

use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Domains\Subscription\Enums\SubscriptionTier;
use App\Domains\Subscription\Models\Subscription;
use App\Models\User;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, HasMedia, HasSlug, HasUuid, HasVideoEmbeds, SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'slug',
        'domain',
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
            if (empty($provider->domain)) {
                $provider->domain = static::generateDomain($provider->business_name);
            }
        });

        static::updating(function ($provider) {
            if ($provider->isDirty('business_name') && ! $provider->isDirty('slug')) {
                $provider->slug = static::generateSlug($provider->business_name, $provider->id);
            }
            if ($provider->isDirty('business_name') && ! $provider->isDirty('domain')) {
                $provider->domain = static::generateDomain($provider->business_name, $provider->id);
            }
        });
    }

    /**
     * Generate a unique domain from a business name.
     */
    public static function generateDomain(string $value, ?int $excludeId = null): string
    {
        $domain = Str::slug($value);
        $originalDomain = $domain;
        $counter = 1;

        while (static::domainExists($domain, $excludeId)) {
            $domain = $originalDomain . '-' . $counter;
            $counter++;
        }

        return $domain;
    }

    /**
     * Check if a domain already exists.
     */
    protected static function domainExists(string $domain, ?int $excludeId = null): bool
    {
        $query = static::where('domain', $domain);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
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
     * Check if the provider has access to a specific feature.
     */
    public function hasFeature(\App\Domains\Subscription\Enums\SubscriptionFeature $feature): bool
    {
        return $this->getTier()->hasFeature($feature);
    }

    /**
     * Get all features available to this provider.
     *
     * @return array<\App\Domains\Subscription\Enums\SubscriptionFeature>
     */
    public function getAvailableFeatures(): array
    {
        return $this->getTier()->features();
    }

    /**
     * Get all features with availability status for this provider.
     */
    public function getAllFeaturesWithStatus(): array
    {
        return $this->getTier()->allFeaturesWithStatus();
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
            ->whereHas('user', fn($q) => $q->where('is_active', true));
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
        return route('providersite.home', $this->domain);
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

    // =========================================================================
    // Team Member Methods
    // =========================================================================

    /**
     * Get all team members for this provider.
     */
    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Get only active team members.
     */
    public function activeTeamMembers(): HasMany
    {
        return $this->teamMembers()->active();
    }

    /**
     * Get the count of active team members.
     */
    public function getTeamMemberCount(): int
    {
        return $this->activeTeamMembers()->count();
    }

    /**
     * Get the team member limit based on subscription tier.
     * Returns null for unlimited.
     */
    public function getTeamMemberLimit(): ?int
    {
        return $this->getTier()->teamMemberLimit();
    }

    /**
     * Get the number of free team member slots for this tier.
     */
    public function getFreeTeamMemberSlots(): int
    {
        return $this->getTier()->freeTeamMemberSlots();
    }

    /**
     * Check if provider's tier supports team members.
     */
    public function supportsTeam(): bool
    {
        return $this->getTier()->supportsTeam();
    }

    /**
     * Check if the provider can add another team member.
     */
    public function canAddTeamMember(): bool
    {
        if (! $this->supportsTeam()) {
            return false;
        }

        $limit = $this->getTeamMemberLimit();

        // null means unlimited
        if ($limit === null) {
            return true;
        }

        // For premium tier, soft limit with warning (always allow)
        // The controller/frontend should show a warning about extra charges
        return true;
    }

    /**
     * Get the count of team members exceeding the free slots.
     * This is used for billing extra member fees.
     */
    public function getExtraTeamMemberCount(): int
    {
        $freeSlots = $this->getFreeTeamMemberSlots();
        $activeCount = $this->getTeamMemberCount();

        return max(0, $activeCount - $freeSlots);
    }

    /**
     * Check if adding a member would exceed free slots.
     */
    public function wouldExceedFreeSlots(): bool
    {
        $freeSlots = $this->getFreeTeamMemberSlots();
        $activeCount = $this->getTeamMemberCount();

        return $activeCount >= $freeSlots;
    }

    /**
     * Check if a user is the owner of this provider.
     */
    public function isOwner(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    /**
     * Get the team member record for a given user.
     * Returns null if the user is not a team member.
     */
    public function getTeamMemberFor(User $user): ?TeamMember
    {
        return $this->teamMembers()
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Check if a user has access to this provider (owner or team member).
     */
    public function hasAccess(User $user): bool
    {
        if ($this->isOwner($user)) {
            return true;
        }

        $teamMember = $this->getTeamMemberFor($user);

        return $teamMember && $teamMember->isActive();
    }

    /**
     * Get permissions for a user accessing this provider.
     * Returns all permissions for owner, or the team member's permissions.
     *
     * @return array<string>
     */
    public function getPermissionsFor(User $user): array
    {
        if ($this->isOwner($user)) {
            return \App\Domains\Provider\Enums\TeamPermission::keys();
        }

        $teamMember = $this->getTeamMemberFor($user);

        if (! $teamMember || ! $teamMember->isActive()) {
            return [];
        }

        return $teamMember->permissions ?? [];
    }

    /**
     * Check if a user has a specific permission for this provider.
     */
    public function userHasPermission(User $user, string $permission): bool
    {
        return in_array($permission, $this->getPermissionsFor($user), true);
    }
}
