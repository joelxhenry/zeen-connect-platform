<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Models\ScheduledPayout;
use App\Domains\Provider\Models\Provider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WiPayDisbursementService
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $platformAccountId;

    protected bool $testMode;

    public function __construct()
    {
        $this->testMode = (bool) config('services.wipay.test_mode', true);
        $this->baseUrl = $this->testMode
            ? 'https://sandbox.wipayfinancial.com/v1'
            : 'https://api.wipayfinancial.com/v1';
        $this->apiKey = config('services.wipay.api_key') ?? '';
        $this->platformAccountId = config('services.wipay.platform_account_id') ?? '';
    }

    /**
     * Process a disbursement for a scheduled payout.
     *
     * @return array{success: bool, disbursement_id?: string, error?: string, response?: array}
     */
    public function disburse(ScheduledPayout $payout): array
    {
        $provider = $payout->provider;

        // If provider has WiPay account, disburse to their account
        if ($this->providerHasWiPayAccount($provider)) {
            return $this->disburseToWiPayAccount($payout, $provider);
        }

        // If provider only has banking info, disburse to bank account
        if ($provider->hasBankingInfo()) {
            return $this->disburseToBankAccount($payout, $provider);
        }

        return [
            'success' => false,
            'error' => 'Provider has no payment method configured',
        ];
    }

    /**
     * Disburse to provider's WiPay account.
     */
    protected function disburseToWiPayAccount(ScheduledPayout $payout, Provider $provider): array
    {
        $gatewayConfig = $provider->activeGatewayConfig;

        if (! $gatewayConfig) {
            return [
                'success' => false,
                'error' => 'Provider has no active gateway config',
            ];
        }

        $credentials = $gatewayConfig->getDecryptedCredentials();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/disbursements/account", [
                    'source_account' => $this->platformAccountId,
                    'destination_account' => $credentials['account_number'] ?? '',
                    'amount' => $this->formatAmount($payout->amount),
                    'currency' => $payout->currency,
                    'reference' => $payout->uuid,
                    'description' => "Payout for {$provider->business_name} (Provider #{$provider->id})",
                    'environment' => $this->testMode ? 'sandbox' : 'live',
                ]);

            return $this->handleResponse($response, $payout);
        } catch (\Exception $e) {
            Log::error('WiPay disbursement error (account)', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Disbursement service unavailable',
            ];
        }
    }

    /**
     * Disburse to provider's bank account.
     */
    protected function disburseToBankAccount(ScheduledPayout $payout, Provider $provider): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/disbursements/bank", [
                    'source_account' => $this->platformAccountId,
                    'bank_name' => $provider->bank_name,
                    'account_number' => $provider->bank_account_number,
                    'account_holder_name' => $provider->bank_account_holder_name,
                    'branch_code' => $provider->bank_branch_code,
                    'account_type' => $provider->bank_account_type,
                    'amount' => $this->formatAmount($payout->amount),
                    'currency' => $payout->currency,
                    'reference' => $payout->uuid,
                    'description' => "Payout for {$provider->business_name} (Provider #{$provider->id})",
                    'environment' => $this->testMode ? 'sandbox' : 'live',
                ]);

            return $this->handleResponse($response, $payout);
        } catch (\Exception $e) {
            Log::error('WiPay disbursement error (bank)', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Disbursement service unavailable',
            ];
        }
    }

    /**
     * Handle API response.
     */
    protected function handleResponse($response, ScheduledPayout $payout): array
    {
        $data = $response->json();

        if ($response->successful() && ($data['status'] ?? '') === 'success') {
            Log::info('WiPay disbursement successful', [
                'payout_id' => $payout->id,
                'disbursement_id' => $data['disbursement_id'] ?? null,
            ]);

            return [
                'success' => true,
                'disbursement_id' => $data['disbursement_id'] ?? null,
                'response' => $data,
            ];
        }

        Log::warning('WiPay disbursement failed', [
            'payout_id' => $payout->id,
            'response' => $data,
        ]);

        return [
            'success' => false,
            'error' => $data['message'] ?? 'Disbursement failed',
            'response' => $data,
        ];
    }

    /**
     * Check if provider has a verified WiPay account.
     */
    protected function providerHasWiPayAccount(Provider $provider): bool
    {
        return $provider->gatewayConfigs()
            ->where('is_active', true)
            ->where('verification_status', 'verified')
            ->whereHas('gateway', fn ($q) => $q->where('slug', 'wipay'))
            ->exists();
    }

    /**
     * Get API headers.
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
    }

    /**
     * Format amount to 2 decimal places.
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Check if disbursement service is available.
     */
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->platformAccountId);
    }
}
