<?php

namespace App\Domains\Subscription\Models;

use App\Domains\Provider\Models\Provider;
use App\Domains\Subscription\Enums\InvoiceStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionInvoice extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'subscription_id',
        'provider_id',
        'invoice_number',
        'tier',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'period_start',
        'period_end',
        'powertranz_transaction_id',
        'powertranz_response',
        'paid_at',
        'retry_count',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'tier' => SubscriptionTier::class,
            'status' => InvoiceStatus::class,
            'amount' => 'decimal:2',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'powertranz_response' => 'array',
            'paid_at' => 'datetime',
            'retry_count' => 'integer',
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

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

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

    public function scopeForSubscription($query, int $subscriptionId)
    {
        return $query->where('subscription_id', $subscriptionId);
    }

    public function scopePaid($query)
    {
        return $query->where('status', InvoiceStatus::PAID);
    }

    public function scopePending($query)
    {
        return $query->where('status', InvoiceStatus::PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', InvoiceStatus::FAILED);
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

    protected function periodDisplay(): Attribute
    {
        return Attribute::get(function () {
            $start = $this->period_start->format('M j, Y');
            $end = $this->period_end->format('M j, Y');

            return "{$start} - {$end}";
        });
    }

    protected function billingCycleDisplay(): Attribute
    {
        return Attribute::get(fn () => ucfirst($this->billing_cycle));
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        // Get the next sequence number for this month
        $lastInvoice = static::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -5);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('%s-%s%s-%05d', $prefix, $year, $month, $nextSequence);
    }

    public function markAsPaid(string $transactionId, ?array $response = null): void
    {
        $this->update([
            'status' => InvoiceStatus::PAID,
            'powertranz_transaction_id' => $transactionId,
            'powertranz_response' => $response,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, ?array $response = null): void
    {
        $this->update([
            'status' => InvoiceStatus::FAILED,
            'failure_reason' => $reason,
            'powertranz_response' => $response,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function markAsRefunded(): void
    {
        $this->update([
            'status' => InvoiceStatus::REFUNDED,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === InvoiceStatus::PAID;
    }

    public function isPending(): bool
    {
        return $this->status === InvoiceStatus::PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === InvoiceStatus::FAILED;
    }

    public function canRetry(): bool
    {
        return $this->isFailed() && $this->retry_count < 3;
    }
}
