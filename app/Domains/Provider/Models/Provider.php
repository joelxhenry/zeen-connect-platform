<?php

namespace App\Domains\Provider\Models;

use App\Domains\Event\Models\Event;
use App\Domains\Industry\Models\Industry;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Media\Traits\HasVideoEmbeds;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\ProviderGatewayConfig;
use App\Domains\Payment\Models\ScheduledPayout;
use App\Domains\Payment\Services\LedgerService;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Category;
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
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, HasMedia, HasSlug, HasUuid, HasVideoEmbeds, SoftDeletes;

    protected $fillable = [
        'user_id',
        'industry_id',
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
        'fee_payer',
        'buffer_minutes',
        'deposit_percentage',
        'is_founding_member',
        'founding_member_at',
        // Branding (JSON)
        'branding',
        // Site template
        'site_template',
        // Site features (JSON) - configurable feature cards for templates
        'site_features',
        // Banking info for escrow payouts
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'bank_branch_code',
        'bank_account_type',
        'banking_info_verified',
        'banking_info_verified_at',
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
            'buffer_minutes' => 'integer',
            'deposit_percentage' => 'decimal:2',
            'is_founding_member' => 'boolean',
            'founding_member_at' => 'datetime',
            'branding' => 'array',
            'site_features' => 'array',
            'banking_info_verified' => 'boolean',
            'banking_info_verified_at' => 'datetime',
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
     * Get the industry this provider belongs to.
     */
    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    /**
     * Get all services offered by this provider.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all events offered by this provider.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get all categories owned by this provider.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get service categories for this provider.
     */
    public function serviceCategories(): HasMany
    {
        return $this->categories()->forServices();
    }

    /**
     * Get event categories for this provider.
     */
    public function eventCategories(): HasMany
    {
        return $this->categories()->forEvents();
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
     * Get the provider's availability breaks.
     */
    public function breaks(): MorphMany
    {
        return $this->morphMany(AvailabilityBreak::class, 'scheduleable');
    }

    /**
     * Get the buffer minutes between bookings.
     * Service buffer overrides provider buffer if set.
     */
    public function getBufferMinutes(): int
    {
        return $this->buffer_minutes ?? 0;
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
        return $this->subscription?->tier ?? SubscriptionTier::STARTER;
    }

    /**
     * Check if provider is on starter tier.
     */
    public function isStarterTier(): bool
    {
        return $this->getTier() === SubscriptionTier::STARTER;
    }

    /**
     * @deprecated Use isStarterTier() instead
     */
    public function isFreeTier(): bool
    {
        return $this->isStarterTier();
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

    // =========================================================================
    // Branding Methods
    // =========================================================================

    /**
     * Get a branding value from the JSON column.
     */
    public function getBrandingValue(string $key, mixed $default = null): mixed
    {
        return $this->branding[$key] ?? $default;
    }

    /**
     * Get the brand primary color.
     */
    public function getBrandPrimaryColorAttribute(): ?string
    {
        return $this->getBrandingValue('primary_color');
    }

    /**
     * Get the brand text color.
     */
    public function getBrandTextColorAttribute(): ?string
    {
        return $this->getBrandingValue('text_color');
    }

    /**
     * Get the brand success color.
     */
    public function getBrandSuccessColorAttribute(): ?string
    {
        return $this->getBrandingValue('success_color');
    }

    /**
     * Get the brand warning color.
     */
    public function getBrandWarningColorAttribute(): ?string
    {
        return $this->getBrandingValue('warning_color');
    }

    /**
     * Get the brand danger color.
     */
    public function getBrandDangerColorAttribute(): ?string
    {
        return $this->getBrandingValue('danger_color');
    }

    /**
     * Get the brand info color.
     */
    public function getBrandInfoColorAttribute(): ?string
    {
        return $this->getBrandingValue('info_color');
    }

    /**
     * Get the brand secondary color.
     */
    public function getBrandSecondaryColorAttribute(): ?string
    {
        return $this->getBrandingValue('secondary_color');
    }

    /**
     * Get the brand primary color as RGB values (for opacity support).
     */
    public function getBrandPrimaryRgbAttribute(): ?string
    {
        $primaryColor = $this->brand_primary_color;

        if (! $primaryColor) {
            return null;
        }

        $hex = ltrim($primaryColor, '#');

        return implode(', ', [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ]);
    }

    /**
     * Get the brand hover color (darkened version of primary).
     */
    public function getBrandHoverColorAttribute(): ?string
    {
        $primaryColor = $this->brand_primary_color;

        if (! $primaryColor) {
            return null;
        }

        return $this->darkenColor($primaryColor, 15);
    }

    /**
     * Darken a hex color by a percentage.
     */
    protected function darkenColor(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, (int) ($r * (100 - $percent) / 100));
        $g = max(0, (int) ($g * (100 - $percent) / 100));
        $b = max(0, (int) ($b * (100 - $percent) / 100));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    // =========================================================================
    // Site Template Methods
    // =========================================================================

    /**
     * Get the site template for this provider.
     */
    public function getSiteTemplate(): \App\Domains\ProviderSite\Enums\TemplateType
    {
        return \App\Domains\ProviderSite\Enums\TemplateType::tryFrom($this->site_template)
            ?? \App\Domains\ProviderSite\Enums\TemplateType::DEFAULT;
    }

    /**
     * Check if provider can use a specific template based on their tier.
     */
    public function canUseTemplate(\App\Domains\ProviderSite\Enums\TemplateType $template): bool
    {
        return $template->isAvailableForTier($this->getTier());
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

    // =========================================================================
    // Gateway & Payment Methods
    // =========================================================================

    /**
     * Get all gateway configurations for this provider.
     */
    public function gatewayConfigs(): HasMany
    {
        return $this->hasMany(ProviderGatewayConfig::class);
    }

    /**
     * Get the active and verified gateway configuration.
     */
    public function activeGatewayConfig(): HasOne
    {
        return $this->hasOne(ProviderGatewayConfig::class)
            ->where('is_active', true)
            ->whereNotNull('verified_at');
    }

    /**
     * Get all ledger entries for this provider.
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Get all scheduled payouts for this provider.
     */
    public function scheduledPayouts(): HasMany
    {
        return $this->hasMany(ScheduledPayout::class);
    }

    /**
     * Get the provider's virtual balance (total credits - debits).
     */
    public function getVirtualBalance(): float
    {
        return app(LedgerService::class)->getProviderBalance($this->id);
    }

    /**
     * Get the provider's available balance (excludes held funds).
     */
    public function getAvailableBalance(): float
    {
        return app(LedgerService::class)->getAvailableBalance($this->id);
    }

    /**
     * Check if the provider has a linked and verified payment gateway.
     */
    public function hasLinkedGateway(): bool
    {
        return $this->activeGatewayConfig()->exists();
    }

    /**
     * Get the provider's preferred gateway type.
     */
    public function getPreferredGatewayType(): ?string
    {
        return $this->activeGatewayConfig?->gateway?->type;
    }

    // =========================================================================
    // Banking Info Methods
    // =========================================================================

    /**
     * Check if the provider has banking information configured.
     */
    public function hasBankingInfo(): bool
    {
        return ! empty($this->bank_account_number) && ! empty($this->bank_name);
    }

    /**
     * Check if the provider has any payout method configured.
     * Either WiPay account or banking info for escrow payouts.
     */
    public function hasPayoutMethod(): bool
    {
        return $this->hasLinkedGateway() || $this->hasBankingInfo();
    }

    /**
     * Get the provider's payout method.
     *
     * @return string 'wipay_split', 'bank_transfer', or 'none'
     */
    public function getPayoutMethod(): string
    {
        if ($this->hasLinkedGateway()) {
            return 'wipay_split';
        }

        if ($this->hasBankingInfo()) {
            return 'bank_transfer';
        }

        return 'none';
    }

    // =========================================================================
    // Founding Member Methods
    // =========================================================================
    //
    // Founding member status is independent of current subscription tier.
    // When a founding member upgrades their tier, they RETAIN:
    // - Their founding member status
    // - Their fee waiver (until it expires, derived from founding_member_at)
    // - Their locked price (derived from their tier at founding_member_at)
    //
    // This ensures early adopters keep their benefits even as they grow.
    // =========================================================================

    /**
     * Check if the provider is a founding member.
     * This status persists across tier upgrades.
     */
    public function isFoundingMember(): bool
    {
        return $this->is_founding_member === true;
    }

    /**
     * Get the subscription tier the provider had when marked as founding member.
     * This is derived from their subscription history at founding_member_at.
     */
    public function getFoundingSubscriptionTier(): ?SubscriptionTier
    {
        if (! $this->isFoundingMember() || ! $this->founding_member_at) {
            return null;
        }

        // Find the subscription that was active at founding_member_at
        $subscription = $this->subscriptions()
            ->where('started_at', '<=', $this->founding_member_at)
            ->orderByDesc('started_at')
            ->first();

        // Default to STARTER if no subscription found (shouldn't happen for founders)
        $tier = $subscription?->tier ?? SubscriptionTier::STARTER;

        // Only Premium and Enterprise are founding-eligible
        if (! $tier->isFoundingEligible()) {
            return null;
        }

        return $tier;
    }

    /**
     * Get the date when the founding fee waiver ends.
     * Derived from founding_member_at + tier waiver months.
     */
    public function getFoundingFeeWaiverEndsAt(): ?\Carbon\Carbon
    {
        if (! $this->isFoundingMember() || ! $this->founding_member_at) {
            return null;
        }

        $tier = $this->getFoundingSubscriptionTier();
        if (! $tier) {
            return null;
        }

        $waiverMonths = $tier->foundingFeeWaiverMonths();

        return $this->founding_member_at->copy()->addMonths($waiverMonths);
    }

    /**
     * Check if the founding member has an active fee waiver.
     * Derived from founding_member_at + tier waiver period.
     */
    public function hasFoundingFeeWaiver(): bool
    {
        $waiverEndsAt = $this->getFoundingFeeWaiverEndsAt();

        return $waiverEndsAt !== null && $waiverEndsAt->isFuture();
    }

    /**
     * Get the locked subscription price for founding members.
     * Derived from their tier at founding_member_at.
     */
    public function getFoundingLockedPrice(): ?float
    {
        $tier = $this->getFoundingSubscriptionTier();

        return $tier?->foundingMonthlyPrice();
    }

    /**
     * Check if this is a growth (Premium) founding member.
     */
    public function isGrowthFounder(): bool
    {
        return $this->getFoundingSubscriptionTier() === SubscriptionTier::PREMIUM;
    }

    /**
     * Check if this is an enterprise founding member.
     */
    public function isEnterpriseFounder(): bool
    {
        return $this->getFoundingSubscriptionTier() === SubscriptionTier::ENTERPRISE;
    }
}
