<?php

namespace App\Domains\Payment\Models;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Provider\Models\Provider;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LedgerEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'provider_id',
        'booking_id',
        'payment_id',
        'payout_id',
        'amount',
        'type',
        'balance_after',
        'currency',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'type' => LedgerEntryType::class,
        'metadata' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LedgerEntry $entry) {
            if (empty($entry->uuid)) {
                $entry->uuid = (string) Str::uuid();
            }
        });
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
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

    public function scopeOfType($query, LedgerEntryType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCredits($query)
    {
        return $query->where('type', LedgerEntryType::CREDIT);
    }

    public function scopeDebits($query)
    {
        return $query->where('type', LedgerEntryType::DEBIT);
    }

    public function scopeHolds($query)
    {
        return $query->where('type', LedgerEntryType::HOLD);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    protected function amountDisplay(): Attribute
    {
        return Attribute::get(function () {
            $prefix = $this->type->increasesBalance() ? '+' : '-';

            return $prefix . '$' . number_format(abs($this->amount), 2);
        });
    }

    protected function balanceAfterDisplay(): Attribute
    {
        return Attribute::get(fn () => '$' . number_format($this->balance_after, 2));
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function isCredit(): bool
    {
        return $this->type === LedgerEntryType::CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === LedgerEntryType::DEBIT;
    }

    public function isHold(): bool
    {
        return $this->type === LedgerEntryType::HOLD;
    }

    public function isRelease(): bool
    {
        return $this->type === LedgerEntryType::RELEASE;
    }

    /**
     * Get metadata value.
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }
}
