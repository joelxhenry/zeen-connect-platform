<?php

namespace App\Domains\Payment\Models;

use App\Domains\Payment\Enums\ScheduledPayoutStatus;
use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ScheduledPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'provider_id',
        'amount',
        'currency',
        'scheduled_for',
        'status',
        'batch_id',
        'processed_at',
        'processed_by',
        'payout_method',
        'reference_number',
        'notes',
        'failure_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'scheduled_for' => 'datetime',
        'processed_at' => 'datetime',
        'status' => ScheduledPayoutStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ScheduledPayout $payout) {
            if (empty($payout->uuid)) {
                $payout->uuid = (string) Str::uuid();
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

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(LedgerEntry::class, 'payout_id');
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

    public function scopeStatus($query, ScheduledPayoutStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', ScheduledPayoutStatus::PENDING);
    }

    public function scopeDue($query)
    {
        return $query->pending()
            ->where('scheduled_for', '<=', now());
    }

    public function scopeInBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
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

    protected function scheduledForDisplay(): Attribute
    {
        return Attribute::get(fn () => $this->scheduled_for->format('M d, Y'));
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === ScheduledPayoutStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === ScheduledPayoutStatus::PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === ScheduledPayoutStatus::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === ScheduledPayoutStatus::FAILED;
    }

    public function isDue(): bool
    {
        return $this->isPending() && $this->scheduled_for->isPast();
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function canBeRetried(): bool
    {
        return $this->status->canBeRetried();
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => ScheduledPayoutStatus::PROCESSING]);
    }

    public function markAsCompleted(string $referenceNumber, ?int $processedBy = null): void
    {
        $this->update([
            'status' => ScheduledPayoutStatus::COMPLETED,
            'reference_number' => $referenceNumber,
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);
    }

    public function markAsFailed(string $reason, ?int $processedBy = null): void
    {
        $this->update([
            'status' => ScheduledPayoutStatus::FAILED,
            'failure_reason' => $reason,
            'processed_at' => now(),
            'processed_by' => $processedBy,
        ]);
    }

    public function markAsCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => ScheduledPayoutStatus::CANCELLED,
            'notes' => $reason ?? $this->notes,
        ]);
    }

    /**
     * Generate a unique batch ID.
     */
    public static function generateBatchId(): string
    {
        return 'BATCH-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }
}
