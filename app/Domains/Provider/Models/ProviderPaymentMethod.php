<?php

namespace App\Domains\Provider\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderPaymentMethod extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'provider_id',
        'powertranz_token',
        'card_brand',
        'card_last_four',
        'card_expiry',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getCardDisplayAttribute(): string
    {
        $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Card';

        return "{$brand} ending in {$this->card_last_four}";
    }

    public function getCardIconAttribute(): string
    {
        return match (strtolower($this->card_brand ?? '')) {
            'visa' => 'pi pi-credit-card',
            'mastercard' => 'pi pi-credit-card',
            'amex' => 'pi pi-credit-card',
            'discover' => 'pi pi-credit-card',
            default => 'pi pi-credit-card',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function setAsDefault(): void
    {
        // Remove default from other payment methods
        static::where('provider_id', $this->provider_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    public function isExpired(): bool
    {
        if (! $this->card_expiry) {
            return false;
        }

        [$month, $year] = explode('/', $this->card_expiry);
        $expiryDate = \Carbon\Carbon::createFromFormat('m/Y', "{$month}/{$year}")->endOfMonth();

        return $expiryDate->isPast();
    }
}
