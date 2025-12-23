<?php

namespace App\Domains\Payment\Models;

use App\Domains\Payment\Enums\PayoutStatus;
use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'provider_id',
        'amount',
        'currency',
        'period_start',
        'period_end',
        'payout_method',
        'bank_name',
        'bank_account_last_four',
        'reference_number',
        'status',
        'notes',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'status' => PayoutStatus::class,
        'processed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Payout $payout) {
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

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus($query, PayoutStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', PayoutStatus::PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', PayoutStatus::COMPLETED);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
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
        return Attribute::get(fn () => $this->period_start->format('M j') . ' - ' . $this->period_end->format('M j, Y'));
    }

    protected function bankAccountDisplay(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->bank_account_last_four) {
                return null;
            }

            $bank = $this->bank_name ?? 'Bank';

            return "{$bank} ****{$this->bank_account_last_four}";
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === PayoutStatus::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === PayoutStatus::COMPLETED;
    }

    public function markAsProcessing(User $admin): void
    {
        $this->update([
            'status' => PayoutStatus::PROCESSING,
            'processed_by' => $admin->id,
        ]);
    }

    public function markAsCompleted(string $referenceNumber): void
    {
        $this->update([
            'status' => PayoutStatus::COMPLETED,
            'reference_number' => $referenceNumber,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(string $notes): void
    {
        $this->update([
            'status' => PayoutStatus::FAILED,
            'notes' => $notes,
        ]);
    }
}
