<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Enums\ScheduledPayoutStatus;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\ScheduledPayout;
use App\Domains\Provider\Models\Provider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayoutScheduler
{
    public function __construct(
        protected LedgerService $ledgerService
    ) {}

    /**
     * Schedule payouts for all eligible providers.
     */
    public function schedulePayouts(): int
    {
        $providers = $this->getEligibleProviders();
        $scheduledCount = 0;

        foreach ($providers as $provider) {
            $payoutAmount = $this->calculatePayoutAmount($provider);

            if ($payoutAmount <= 0) {
                continue;
            }

            // Check if there's already a pending payout for this provider
            $existingPayout = ScheduledPayout::where('provider_id', $provider->id)
                ->whereIn('status', [
                    ScheduledPayoutStatus::PENDING,
                    ScheduledPayoutStatus::PROCESSING,
                ])
                ->exists();

            if ($existingPayout) {
                continue;
            }

            ScheduledPayout::create([
                'provider_id' => $provider->id,
                'amount' => $payoutAmount,
                'currency' => 'JMD',
                'scheduled_for' => $this->getNextPayoutDate(),
                'status' => ScheduledPayoutStatus::PENDING,
                'payout_method' => $this->determinePayoutMethod($provider),
            ]);

            $scheduledCount++;

            Log::info('Payout scheduled', [
                'provider_id' => $provider->id,
                'amount' => $payoutAmount,
            ]);
        }

        return $scheduledCount;
    }

    /**
     * Process all due scheduled payouts.
     */
    public function processScheduledPayouts(): int
    {
        $duePayouts = ScheduledPayout::due()
            ->with('provider')
            ->get();

        $processedCount = 0;

        foreach ($duePayouts as $payout) {
            try {
                $this->processPayout($payout);
                $processedCount++;
            } catch (\Exception $e) {
                Log::error('Payout processing failed', [
                    'payout_id' => $payout->id,
                    'error' => $e->getMessage(),
                ]);

                $payout->markAsFailed($e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * Process a single scheduled payout.
     */
    public function processPayout(ScheduledPayout $payout): bool
    {
        return DB::transaction(function () use ($payout) {
            $payout->markAsProcessing();

            // Verify the provider still has sufficient balance
            $availableBalance = $this->ledgerService->getAvailableBalance($payout->provider_id);

            if ($availableBalance < $payout->amount) {
                // Adjust payout amount to available balance
                if ($availableBalance >= $this->getMinimumPayoutAmount()) {
                    $payout->update(['amount' => $availableBalance]);
                } else {
                    $payout->markAsFailed('Insufficient balance');

                    return false;
                }
            }

            // Create a ledger entry for the payout (debit)
            $ledgerEntry = $this->ledgerService->debitForPayout($payout->provider, $payout);

            // Generate reference number
            $referenceNumber = $this->generateReferenceNumber();

            // Mark as completed (actual bank transfer would be handled separately)
            $payout->markAsCompleted($referenceNumber);

            Log::info('Payout processed', [
                'payout_id' => $payout->id,
                'provider_id' => $payout->provider_id,
                'amount' => $payout->amount,
                'reference' => $referenceNumber,
            ]);

            return true;
        });
    }

    /**
     * Get providers eligible for payout.
     */
    public function getEligibleProviders(): Collection
    {
        $minimumAmount = $this->getMinimumPayoutAmount();

        // Get providers who only use escrow payments (have balance in ledger)
        return Provider::query()
            ->whereHas('ledgerEntries', function ($query) use ($minimumAmount) {
                $query->select('provider_id')
                    ->groupBy('provider_id')
                    ->havingRaw('SUM(CASE WHEN type IN (?, ?) THEN amount ELSE -amount END) >= ?', [
                        LedgerEntryType::CREDIT->value,
                        LedgerEntryType::RELEASE->value,
                        $minimumAmount,
                    ]);
            })
            ->with(['activeGatewayConfig', 'subscription'])
            ->get()
            ->filter(function ($provider) {
                // Only include providers using escrow (no linked gateway or escrow gateway)
                $gatewayConfig = $provider->activeGatewayConfig;

                if (! $gatewayConfig) {
                    return true; // Default to escrow
                }

                return $gatewayConfig->gateway?->type === GatewayType::ESCROW->value;
            });
    }

    /**
     * Calculate the payout amount for a provider.
     */
    public function calculatePayoutAmount(Provider $provider): float
    {
        return $this->ledgerService->getAvailableBalance($provider->id);
    }

    /**
     * Get the next scheduled payout date.
     */
    protected function getNextPayoutDate(): \Carbon\Carbon
    {
        $frequency = config('payment.payout_schedule.frequency', 'weekly');
        $dayOfWeek = config('payment.payout_schedule.day_of_week', 'friday');

        return match ($frequency) {
            'daily' => now()->addDay()->startOfDay(),
            'weekly' => now()->next($dayOfWeek)->startOfDay(),
            'biweekly' => now()->next($dayOfWeek)->addWeek()->startOfDay(),
            'monthly' => now()->addMonth()->startOfMonth()->startOfDay(),
            default => now()->next($dayOfWeek)->startOfDay(),
        };
    }

    /**
     * Get the minimum payout amount.
     */
    protected function getMinimumPayoutAmount(): float
    {
        return config('payment.payout_schedule.minimum_amount', 1000);
    }

    /**
     * Determine the payout method for a provider.
     */
    protected function determinePayoutMethod(Provider $provider): string
    {
        // Could be extended to support multiple payout methods (bank transfer, etc.)
        return 'bank_transfer';
    }

    /**
     * Generate a unique payout reference number.
     */
    protected function generateReferenceNumber(): string
    {
        return 'PAYOUT-' . now()->format('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
    }

    /**
     * Create a batch of payouts for processing.
     */
    public function createBatch(array $payoutIds): string
    {
        $batchId = ScheduledPayout::generateBatchId();

        ScheduledPayout::whereIn('id', $payoutIds)
            ->where('status', ScheduledPayoutStatus::PENDING)
            ->update(['batch_id' => $batchId]);

        return $batchId;
    }

    /**
     * Process all payouts in a batch.
     */
    public function processBatch(string $batchId): array
    {
        $payouts = ScheduledPayout::inBatch($batchId)
            ->pending()
            ->get();

        $results = [
            'total' => $payouts->count(),
            'processed' => 0,
            'failed' => 0,
        ];

        foreach ($payouts as $payout) {
            try {
                if ($this->processPayout($payout)) {
                    $results['processed']++;
                } else {
                    $results['failed']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $payout->markAsFailed($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Cancel a scheduled payout.
     */
    public function cancelPayout(ScheduledPayout $payout, string $reason): bool
    {
        if (! $payout->canBeCancelled()) {
            return false;
        }

        $payout->markAsCancelled($reason);

        Log::info('Payout cancelled', [
            'payout_id' => $payout->id,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Retry a failed payout.
     */
    public function retryPayout(ScheduledPayout $payout): ?ScheduledPayout
    {
        if (! $payout->canBeRetried()) {
            return null;
        }

        // Create a new scheduled payout
        $newPayout = ScheduledPayout::create([
            'provider_id' => $payout->provider_id,
            'amount' => $this->calculatePayoutAmount($payout->provider),
            'currency' => $payout->currency,
            'scheduled_for' => $this->getNextPayoutDate(),
            'status' => ScheduledPayoutStatus::PENDING,
            'payout_method' => $payout->payout_method,
            'notes' => "Retry of payout #{$payout->uuid}",
        ]);

        Log::info('Payout retry scheduled', [
            'original_payout_id' => $payout->id,
            'new_payout_id' => $newPayout->id,
        ]);

        return $newPayout;
    }
}
