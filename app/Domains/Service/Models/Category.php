<?php

namespace App\Domains\Service\Models;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Enums\CategoryType;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    use HasFactory, HasUuid, HasSlug;

    protected $fillable = [
        'provider_id',
        'name',
        'slug',
        'description',
        'type',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            if (empty($category->slug)) {
                $category->slug = static::generateSlugForProvider(
                    $category->name,
                    $category->provider_id,
                    $category->type ?? CategoryType::SERVICE
                );
            }
        });

        static::updating(function (Category $category) {
            if ($category->isDirty('name')) {
                $category->slug = static::generateSlugForProvider(
                    $category->name,
                    $category->provider_id,
                    $category->type ?? CategoryType::SERVICE,
                    $category->id
                );
            }
        });
    }

    /**
     * Generate a slug that's unique per provider and type.
     */
    public static function generateSlugForProvider(
        string $name,
        int $providerId,
        CategoryType $type,
        ?int $excludeId = null
    ): string {
        $slug = \Illuminate\Support\Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::slugExistsForProvider($slug, $providerId, $type, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists for a provider and type.
     */
    protected static function slugExistsForProvider(
        string $slug,
        int $providerId,
        CategoryType $type,
        ?int $excludeId = null
    ): bool {
        $query = static::where('slug', $slug)
            ->where('provider_id', $providerId)
            ->where('type', $type);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get the provider that owns this category.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Get all services that have this category (polymorphic).
     */
    public function services(): MorphToMany
    {
        return $this->morphedByMany(Service::class, 'categorizable');
    }

    /**
     * Get the count of services using this category.
     */
    public function getServicesCountAttribute(): int
    {
        return $this->services()->count();
    }

    /**
     * Scope to filter by service type categories.
     */
    public function scopeForServices($query)
    {
        return $query->where('type', CategoryType::SERVICE);
    }

    /**
     * Scope to filter by event type categories.
     */
    public function scopeForEvents($query)
    {
        return $query->where('type', CategoryType::EVENT);
    }

    /**
     * Scope to filter by provider.
     */
    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope to filter active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order categories by sort_order then name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Check if this category is for services.
     */
    public function isServiceCategory(): bool
    {
        return $this->type === CategoryType::SERVICE;
    }

    /**
     * Check if this category is for events.
     */
    public function isEventCategory(): bool
    {
        return $this->type === CategoryType::EVENT;
    }
}
