<?php

namespace App\Domains\Payment\Models;

use App\Domains\Provider\Models\Provider;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ProviderGatewayConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'gateway_id',
        'credentials',
        'merchant_account_id',
        'is_active',
        'is_primary',
        'verified_at',
        'verification_status',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(Gateway::class);
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

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get decrypted credentials.
     */
    public function getDecryptedCredentials(): array
    {
        if (empty($this->attributes['credentials'])) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($this->attributes['credentials']), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set encrypted credentials.
     */
    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Get a specific credential value.
     */
    public function getCredential(string $key, mixed $default = null): mixed
    {
        $credentials = $this->getDecryptedCredentials();

        return $credentials[$key] ?? $default;
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->verification_status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->verification_status === 'failed';
    }

    public function markAsVerified(): void
    {
        $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'is_active' => true,
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'verification_status' => 'failed',
            'is_active' => false,
        ]);
    }

    /**
     * Make this config the primary for the provider.
     */
    public function makePrimary(): void
    {
        // Remove primary from other configs
        static::where('provider_id', $this->provider_id)
            ->where('id', '!=', $this->id)
            ->update(['is_primary' => false]);

        $this->update(['is_primary' => true]);
    }
}
