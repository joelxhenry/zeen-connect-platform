<?php

namespace App\Domains\Location\Models;

use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Domains\Provider\Models\Provider;

class Region extends Model
{
    use HasFactory, HasSlug, HasUuid;

    protected $fillable = [
        'country_id',
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

        static::creating(function ($region) {
            if (empty($region->slug)) {
                $region->slug = static::generateSlug($region->name);
            }
        });
    }

    /**
     * Get the country that this region belongs to.
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the locations in this region.
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    /**
     * Get all providers in this region through locations.
     */
    public function providers(): HasManyThrough
    {
        return $this->hasManyThrough(Provider::class, Location::class);
    }

    /**
     * Scope a query to only include active regions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get full name with country.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name}, {$this->country->name}";
    }
}
