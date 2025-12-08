<?php

namespace App\Domains\Provider\Models;

use App\Domains\Location\Models\Location;
use App\Models\User;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory, HasSlug, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'primary_location_id',
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
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'commission_rate' => 'decimal:2',
            'rating_avg' => 'decimal:2',
            'is_featured' => 'boolean',
            'verified_at' => 'datetime',
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
     * Get the primary location for this provider.
     */
    public function primaryLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'primary_location_id');
    }

    /**
     * Get all locations this provider serves.
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'location_provider')
            ->withPivot('is_primary')
            ->withTimestamps();
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
     * Scope a query to filter by location (providers serving this location).
     */
    public function scopeServesLocation($query, int $locationId)
    {
        return $query->whereHas('locations', fn ($q) => $q->where('locations.id', $locationId));
    }

    /**
     * Scope a query to filter by region (providers serving any location in this region).
     */
    public function scopeServesRegion($query, int $regionId)
    {
        return $query->whereHas('locations', fn ($q) => $q->where('region_id', $regionId));
    }

    /**
     * Scope a query to filter by country (providers serving any location in this country).
     */
    public function scopeServesCountry($query, int $countryId)
    {
        return $query->whereHas('locations.region', fn ($q) => $q->where('country_id', $countryId));
    }

    /**
     * Get the public URL for this provider's profile.
     */
    public function getPublicUrlAttribute(): string
    {
        return route('provider.public', $this->slug);
    }

    /**
     * Get the primary location display string.
     */
    public function getLocationDisplayAttribute(): ?string
    {
        if (! $this->primaryLocation) {
            return null;
        }

        return $this->primaryLocation->display_name;
    }

    /**
     * Get all locations as a comma-separated string.
     */
    public function getLocationsDisplayAttribute(): string
    {
        return $this->locations->pluck('name')->implode(', ');
    }

    /**
     * Sync locations and set primary.
     */
    public function syncLocations(array $locationIds, ?int $primaryLocationId = null): void
    {
        // Prepare pivot data with is_primary flag
        $syncData = [];
        foreach ($locationIds as $locationId) {
            $syncData[$locationId] = [
                'is_primary' => $locationId === $primaryLocationId,
            ];
        }

        $this->locations()->sync($syncData);

        // Update primary_location_id on provider
        if ($primaryLocationId && in_array($primaryLocationId, $locationIds)) {
            $this->update(['primary_location_id' => $primaryLocationId]);
        } elseif (! empty($locationIds)) {
            // Default to first location if no primary specified
            $this->update(['primary_location_id' => $locationIds[0]]);
        }
    }

    /**
     * Update rating statistics.
     */
    public function updateRatingStats(): void
    {
        // This will be implemented when Review model is created
        // $this->rating_avg = $this->reviews()->avg('rating') ?? 0;
        // $this->rating_count = $this->reviews()->count();
        // $this->save();
    }
}
