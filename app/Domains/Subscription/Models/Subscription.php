<?php

namespace App\Domains\Subscription\Models;

use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\ProviderPaymentMethod;
use App\Domains\Subscription\Enums\SubscriptionStatus;
use App\Domains\Subscription\Enums\SubscriptionTier;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'provider_id',
        'tier',
        'status',
        'billing_cycle',
        'trial_ends_at',
        'started_at',
        'expires_at',
        'cancelled_at',
        'grace_period_ends_at',
        'has_used_trial',
        'stripe_subscription_id',
        'powertranz_profile_id',
    ];

    protected $casts = [
        'tier' => SubscriptionTier::class,
        'status' => SubscriptionStatus::class,
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'grace_period_ends_at' => 'datetime',
        'has_used_trial' => 'boolean',
    ];

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SubscriptionFactory
    {
        return SubscriptionFactory::new();
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

    public function invoices(): HasMany
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::ACTIVE);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    public function scopeTrialExpired($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now());
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeInGracePeriod($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '>', now());
    }

    public function scopeGracePeriodExpired($query)
    {
        return $query->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<=', now());
    }

    /*
    |--------------------------------------------------------------------------
    | Status Methods
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        if ($this->status !== SubscriptionStatus::ACTIVE) {
            return false;
        }

        // Free tier never expires
        if ($this->tier === SubscriptionTier::STARTER) {
            return true;
        }

        // On trial - check trial expiration
        if ($this->isOnTrial()) {
            return true;
        }

        // Check expiration for paid tiers
        return $this->expires_at === null || $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function isInGracePeriod(): bool
    {
        return $this->isCancelled()
            && $this->grace_period_ends_at !== null
            && $this->grace_period_ends_at->isFuture();
    }

    /*
    |--------------------------------------------------------------------------
    | Trial Methods
    |--------------------------------------------------------------------------
    */

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function trialDaysRemaining(): int
    {
        if (! $this->isOnTrial()) {
            return 0;
        }

        return (int) now()->diffInDays($this->trial_ends_at, false);
    }

    public function canStartTrial(): bool
    {
        return ! $this->has_used_trial
            && $this->tier === SubscriptionTier::STARTER;
    }

    public function startTrial(SubscriptionTier $tier, int $days = 14): void
    {
        $this->update([
            'tier' => $tier,
            'trial_ends_at' => now()->addDays($days),
            'has_used_trial' => true,
        ]);
    }

    public function endTrial(): void
    {
        $this->update([
            'tier' => SubscriptionTier::STARTER,
            'trial_ends_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Billing Methods
    |--------------------------------------------------------------------------
    */

    public function isMonthly(): bool
    {
        return $this->billing_cycle === 'monthly';
    }

    public function isAnnual(): bool
    {
        return $this->billing_cycle === 'annual';
    }

    public function getCurrentPrice(): float
    {
        return $this->tier->price($this->billing_cycle);
    }

    public function getNextBillingDate(): ?\Carbon\Carbon
    {
        if ($this->tier->isFree() || $this->isOnTrial()) {
            return null;
        }

        return $this->expires_at;
    }

    /*
    |--------------------------------------------------------------------------
    | Upgrade/Downgrade Methods
    |--------------------------------------------------------------------------
    */

    public function upgradeTo(SubscriptionTier $tier, string $billingCycle, string $powertranzProfileId): void
    {
        $expiresAt = $billingCycle === 'annual'
            ? now()->addYear()
            : now()->addMonth();

        $this->update([
            'tier' => $tier,
            'billing_cycle' => $billingCycle,
            'trial_ends_at' => null, // Clear trial if upgrading
            'expires_at' => $expiresAt,
            'powertranz_profile_id' => $powertranzProfileId,
            'cancelled_at' => null, // Clear cancellation
            'grace_period_ends_at' => null,
        ]);
    }

    public function downgradeToStarter(): void
    {
        $this->update([
            'tier' => SubscriptionTier::STARTER,
            'billing_cycle' => 'monthly',
            'expires_at' => null,
            'powertranz_profile_id' => null,
            'cancelled_at' => null,
            'grace_period_ends_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation Methods
    |--------------------------------------------------------------------------
    */

    public function cancel(): void
    {
        $this->update([
            'cancelled_at' => now(),
            'grace_period_ends_at' => $this->expires_at,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'cancelled_at' => null,
            'grace_period_ends_at' => null,
        ]);
    }

    public function processGracePeriodExpiration(): void
    {
        if (! $this->isInGracePeriod() && $this->grace_period_ends_at?->isPast()) {
            $this->update([
                'status' => SubscriptionStatus::CANCELLED,
                'tier' => SubscriptionTier::STARTER,
                'powertranz_profile_id' => null,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Renewal Methods
    |--------------------------------------------------------------------------
    */

    public function extendSubscription(): void
    {
        $newExpiresAt = $this->billing_cycle === 'annual'
            ? $this->expires_at->addYear()
            : $this->expires_at->addMonth();

        $this->update([
            'expires_at' => $newExpiresAt,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Helpers
    |--------------------------------------------------------------------------
    */

    public function getBillingCycleDisplayAttribute(): string
    {
        return ucfirst($this->billing_cycle ?? 'monthly');
    }

    public function getStatusDisplayAttribute(): string
    {
        if ($this->isOnTrial()) {
            return 'Trial (' . $this->trialDaysRemaining() . ' days left)';
        }

        if ($this->isInGracePeriod()) {
            return 'Cancelling (active until ' . $this->grace_period_ends_at->format('M j, Y') . ')';
        }

        return $this->status->label();
    }
}
