<?php

namespace App\Domains\Industry\Models;

use App\Domains\Provider\Models\Provider;
use App\Support\Traits\HasSlug;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Industry extends Model
{
    use HasFactory, HasUuid, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Industry $industry) {
            if (empty($industry->slug)) {
                $industry->slug = static::generateSlug($industry->name);
            }
        });

        static::updating(function (Industry $industry) {
            if ($industry->isDirty('name')) {
                $industry->slug = static::generateSlug($industry->name, $industry->id);
            }
        });
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
