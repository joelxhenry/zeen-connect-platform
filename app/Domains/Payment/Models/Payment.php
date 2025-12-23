<?php

namespace App\Domains\Payment\Models;

use App\Domains\Booking\Models\Booking;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'booking_id',
        'client_id',
        'provider_id',
        'amount',
        'platform_fee',
        'provider_amount',
        'currency',
        'gateway',
        'gateway_transaction_id',
        'gateway_order_id',
        'gateway_response_code',
        'gateway_response',
        'status',
        'failure_reason',
        'card_last_four',
        'card_brand',
        'paid_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'provider_amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payment $payment) {
            if (empty($payment->uuid)) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function payouts(): BelongsToMany
    {
        return $this->belongsToMany(Payout::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeUnpaidOut($query)
    {
        return $query->completed()
            ->whereDoesntHave('payouts');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    protected function amountDisplay(): Attribute
    {
        return Attribute::get(fn () => '$' . number_format($this->amount, 2));
    }

    protected function providerAmountDisplay(): Attribute
    {
        return Attribute::get(fn () => '$' . number_format($this->provider_amount, 2));
    }

    protected function platformFeeDisplay(): Attribute
    {
        return Attribute::get(fn () => '$' . number_format($this->platform_fee, 2));
    }

    protected function cardDisplay(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->card_last_four) {
                return null;
            }

            $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Card';

            return "{$brand} ending in {$this->card_last_four}";
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    public function canBeRefunded(): bool
    {
        return $this->status === PaymentStatus::COMPLETED
            && $this->refunded_at === null;
    }

    public function markAsCompleted(string $transactionId, ?array $response = null): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'gateway_transaction_id' => $transactionId,
            'gateway_response' => $response,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, ?string $responseCode = null, ?array $response = null): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failure_reason' => $reason,
            'gateway_response_code' => $responseCode,
            'gateway_response' => $response,
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => PaymentStatus::REFUNDED,
            'refunded_at' => now(),
        ]);
    }
}
