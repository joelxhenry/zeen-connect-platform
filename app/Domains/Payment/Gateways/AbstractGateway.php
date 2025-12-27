<?php

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected bool $testMode;

    protected string $baseUrl;

    public function __construct()
    {
        $this->testMode = $this->isTestMode();
        $this->baseUrl = $this->getBaseUrl();
    }

    /**
     * Check if gateway is in test/sandbox mode.
     */
    abstract protected function isTestMode(): bool;

    /**
     * Get the base API URL.
     */
    abstract protected function getBaseUrl(): string;

    /**
     * Get HTTP headers for API requests.
     */
    abstract protected function getHeaders(): array;

    /**
     * Check if the gateway is properly configured and available.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get the list of currencies supported by this gateway.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'USD'];
    }

    /**
     * Verify the authenticity of a webhook request.
     * Override in specific gateway implementations.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        // Default implementation - override in specific gateways
        return true;
    }

    /**
     * Log an informational message.
     */
    protected function log(string $message, array $context = []): void
    {
        Log::channel('payments')->info(
            "[{$this->getProvider()}] {$message}",
            $context
        );
    }

    /**
     * Log an error message.
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('payments')->error(
            "[{$this->getProvider()}] {$message}",
            $context
        );
    }

    /**
     * Log a warning message.
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('payments')->warning(
            "[{$this->getProvider()}] {$message}",
            $context
        );
    }

    /**
     * Format amount for the gateway API.
     */
    protected function formatAmount(float $amount): float
    {
        return round($amount, 2);
    }

    /**
     * Get ISO 4217 numeric currency code.
     */
    protected function getCurrencyCode(string $currency): string
    {
        return match (strtoupper($currency)) {
            'JMD' => '388',
            'USD' => '840',
            'TTD' => '780',
            'BBD' => '052',
            'XCD' => '951',
            default => '388', // Default to JMD
        };
    }
}
