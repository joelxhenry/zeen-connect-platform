<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Enums\LedgerEntryType;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Models\ScheduledPayout;
use App\Domains\Provider\Models\Provider;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    /**
     * Credit provider's virtual balance after escrow payment.
     */
    public function creditProvider(Payment $payment): LedgerEntry
    {
        return DB::transaction(function () use ($payment) {
            $currentBalance = $this->getProviderBalance($payment->provider_id);
            $newBalance = $currentBalance + $payment->provider_amount;

            $entry = LedgerEntry::create([
                'provider_id' => $payment->provider_id,
                'booking_id' => $payment->booking_id,
                'payment_id' => $payment->id,
                'amount' => $payment->provider_amount,
                'type' => LedgerEntryType::CREDIT,
                'balance_after' => $newBalance,
                'currency' => $payment->currency,
                'description' => "Payment received for booking #{$payment->booking->uuid}",
                'metadata' => [
                    'total_payment' => $payment->amount,
                    'platform_fee' => $payment->platform_fee,
                    'processing_fee' => $payment->processing_fee ?? 0,
                    'gateway' => $payment->gateway,
                ],
            ]);

            // Link ledger entry to payment
            $payment->update(['ledger_entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * Debit provider's balance for a payout.
     */
    public function debitForPayout(Provider $provider, ScheduledPayout $payout): LedgerEntry
    {
        return DB::transaction(function () use ($provider, $payout) {
            $currentBalance = $this->getProviderBalance($provider->id);
            $newBalance = $currentBalance - $payout->amount;

            return LedgerEntry::create([
                'provider_id' => $provider->id,
                'payout_id' => $payout->id,
                'amount' => $payout->amount,
                'type' => LedgerEntryType::DEBIT,
                'balance_after' => $newBalance,
                'currency' => $payout->currency,
                'description' => "Payout processed - {$payout->reference_number}",
                'metadata' => [
                    'payout_method' => $payout->payout_method,
                    'batch_id' => $payout->batch_id,
                ],
            ]);
        });
    }

    /**
     * Hold funds (e.g., for pending refund or dispute).
     */
    public function holdFunds(
        int $providerId,
        float $amount,
        string $reason,
        ?int $bookingId = null,
        ?int $paymentId = null
    ): LedgerEntry {
        return DB::transaction(function () use ($providerId, $amount, $reason, $bookingId, $paymentId) {
            $currentBalance = $this->getProviderBalance($providerId);
            $newBalance = $currentBalance - $amount;

            return LedgerEntry::create([
                'provider_id' => $providerId,
                'booking_id' => $bookingId,
                'payment_id' => $paymentId,
                'amount' => $amount,
                'type' => LedgerEntryType::HOLD,
                'balance_after' => $newBalance,
                'description' => $reason,
            ]);
        });
    }

    /**
     * Release previously held funds.
     */
    public function releaseFunds(LedgerEntry $holdEntry): LedgerEntry
    {
        if ($holdEntry->type !== LedgerEntryType::HOLD) {
            throw new \InvalidArgumentException('Can only release HOLD entries');
        }

        return DB::transaction(function () use ($holdEntry) {
            $currentBalance = $this->getProviderBalance($holdEntry->provider_id);
            $newBalance = $currentBalance + $holdEntry->amount;

            return LedgerEntry::create([
                'provider_id' => $holdEntry->provider_id,
                'booking_id' => $holdEntry->booking_id,
                'payment_id' => $holdEntry->payment_id,
                'amount' => $holdEntry->amount,
                'type' => LedgerEntryType::RELEASE,
                'balance_after' => $newBalance,
                'description' => "Released: {$holdEntry->description}",
                'metadata' => [
                    'original_hold_id' => $holdEntry->id,
                ],
            ]);
        });
    }

    /**
     * Debit for refund (when provider's share needs to be returned).
     */
    public function debitForRefund(Payment $payment, float $providerRefundAmount): LedgerEntry
    {
        return DB::transaction(function () use ($payment, $providerRefundAmount) {
            $currentBalance = $this->getProviderBalance($payment->provider_id);
            $newBalance = $currentBalance - $providerRefundAmount;

            return LedgerEntry::create([
                'provider_id' => $payment->provider_id,
                'booking_id' => $payment->booking_id,
                'payment_id' => $payment->id,
                'amount' => $providerRefundAmount,
                'type' => LedgerEntryType::DEBIT,
                'balance_after' => $newBalance,
                'currency' => $payment->currency,
                'description' => "Refund processed for booking #{$payment->booking->uuid}",
                'metadata' => [
                    'refund_amount' => $providerRefundAmount,
                    'original_payment_amount' => $payment->amount,
                ],
            ]);
        });
    }

    /**
     * Get provider's current total balance.
     */
    public function getProviderBalance(int $providerId): float
    {
        $lastEntry = LedgerEntry::where('provider_id', $providerId)
            ->orderByDesc('id')
            ->first();

        return $lastEntry?->balance_after ?? 0.0;
    }

    /**
     * Get provider's available balance (excluding held amounts).
     */
    public function getAvailableBalance(int $providerId): float
    {
        $totalBalance = $this->getProviderBalance($providerId);

        // Calculate total held funds (HOLD entries without corresponding RELEASE)
        $heldAmount = $this->getHeldAmount($providerId);

        return $totalBalance - $heldAmount;
    }

    /**
     * Get total amount currently on hold.
     */
    public function getHeldAmount(int $providerId): float
    {
        // Get sum of HOLD entries
        $holdTotal = LedgerEntry::where('provider_id', $providerId)
            ->where('type', LedgerEntryType::HOLD)
            ->sum('amount');

        // Get sum of RELEASE entries
        $releaseTotal = LedgerEntry::where('provider_id', $providerId)
            ->where('type', LedgerEntryType::RELEASE)
            ->sum('amount');

        return max(0, $holdTotal - $releaseTotal);
    }

    /**
     * Get provider's ledger statement.
     *
     * @return \Illuminate\Database\Eloquent\Collection<LedgerEntry>
     */
    public function getStatement(int $providerId, ?int $limit = 50, ?int $offset = 0)
    {
        return LedgerEntry::where('provider_id', $providerId)
            ->orderByDesc('created_at')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Get provider's balance summary.
     *
     * @return array{total: float, available: float, held: float, pending_payout: float}
     */
    public function getBalanceSummary(int $providerId): array
    {
        $total = $this->getProviderBalance($providerId);
        $held = $this->getHeldAmount($providerId);
        $available = $total - $held;

        // Get pending payout amounts
        $pendingPayout = ScheduledPayout::where('provider_id', $providerId)
            ->pending()
            ->sum('amount');

        return [
            'total' => $total,
            'available' => $available,
            'held' => $held,
            'pending_payout' => $pendingPayout,
        ];
    }

    /**
     * Calculate total earnings for a provider in a date range.
     */
    public function getEarningsInRange(int $providerId, \DateTime $start, \DateTime $end): float
    {
        return LedgerEntry::where('provider_id', $providerId)
            ->where('type', LedgerEntryType::CREDIT)
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');
    }
}
