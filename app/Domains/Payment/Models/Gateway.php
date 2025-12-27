<?php

namespace App\Domains\Payment\Models;

use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\GatewayType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'supports_split',
        'supports_escrow',
        'config',
        'supported_currencies',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_split' => 'boolean',
        'supports_escrow' => 'boolean',
        'config' => 'array',
        'supported_currencies' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function providerConfigs(): HasMany
    {
        return $this->hasMany(ProviderGatewayConfig::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSupportsSplit($query)
    {
        return $query->where('supports_split', true);
    }

    public function scopeSupportsEscrow($query)
    {
        return $query->where('supports_escrow', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function getProvider(): GatewayProvider
    {
        return GatewayProvider::from($this->slug);
    }

    public function getTypeEnum(): GatewayType
    {
        return GatewayType::from($this->type);
    }

    public function supportsCurrency(string $currency): bool
    {
        if (empty($this->supported_currencies)) {
            return true; // No restrictions
        }

        return in_array(strtoupper($currency), $this->supported_currencies, true);
    }

    /**
     * Get a specific config value.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Find a gateway by its slug.
     */
    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
