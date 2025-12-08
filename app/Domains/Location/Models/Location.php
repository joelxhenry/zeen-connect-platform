<?php

namespace App\Domains\Location\Models;

use App\Domains\Provider\Models\Provider;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory, HasSlug, HasUuid;

    protected $fillable = [
        'region_id',
        'name',
        'slug',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($location) {
            if (empty($location->slug)) {
                $location->slug = static::generateSlug($location->name);
            }
        });
    }

    /**
     * Get the region that this location belongs to.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the country through region.
     */
    public function country(): BelongsTo
    {
        return $this->region->country();
    }

    /**
     * Get the providers that serve this location.
     */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(Provider::class, 'location_provider')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Get the providers that have this as their primary location.
     */
    public function primaryProviders(): HasMany
    {
        return $this->hasMany(Provider::class, 'primary_location_id');
    }

    /**
     * Scope a query to only include active locations.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full name with region and country.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name}, {$this->region->name}, {$this->region->country->name}";
    }

    /**
     * Get display name with region.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name}, {$this->region->name}";
    }
}
