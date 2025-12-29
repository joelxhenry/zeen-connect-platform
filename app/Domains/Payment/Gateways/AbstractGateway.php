<?php

namespace App\Domains\Payment\Gateways;

use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    protected bool $testMode;

    protected string $baseUrl;

    protected string $correlationId;

    protected array $sensitiveKeys = [
        'password',
        'secret',
        'token',
        'api_key',
        'apikey',
        'authorization',
        'card_number',
        'cvv',
        'cvc',
        'expiry',
        'account_number',
    ];

    public function __construct()
    {
        $this->testMode = $this->isTestMode();
        $this->baseUrl = $this->getBaseUrl();
        $this->correlationId = 'pay_' . Str::random(16);
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
     * Log an outgoing HTTP request.
     */
    protected function logHttpRequest(string $method, string $url, array $data = [], array $headers = []): void
    {
        Log::channel('payments')->info(
            "[{$this->getProvider()}] HTTP Request",
            [
                'correlation_id' => $this->correlationId,
                'method' => $method,
                'url' => $url,
                'headers' => $this->redactSensitiveData($headers),
                'body' => $this->redactSensitiveData($data),
            ]
        );
    }

    /**
     * Log an HTTP response.
     */
    protected function logHttpResponse(Response $response, string $method, string $url, float $durationMs): void
    {
        $level = $response->successful() ? 'info' : 'warning';

        Log::channel('payments')->{$level}(
            "[{$this->getProvider()}] HTTP Response",
            [
                'correlation_id' => $this->correlationId,
                'method' => $method,
                'url' => $url,
                'status' => $response->status(),
                'duration_ms' => round($durationMs, 2),
                'body' => $this->redactSensitiveData($response->json() ?? []),
            ]
        );
    }

    /**
     * Log an HTTP error.
     */
    protected function logHttpError(string $method, string $url, \Throwable $e, float $durationMs): void
    {
        Log::channel('payments')->error(
            "[{$this->getProvider()}] HTTP Error",
            [
                'correlation_id' => $this->correlationId,
                'method' => $method,
                'url' => $url,
                'error' => $e->getMessage(),
                'duration_ms' => round($durationMs, 2),
            ]
        );
    }

    /**
     * Redact sensitive values from data for logging.
     */
    protected function redactSensitiveData(array $data, int $depth = 0): array
    {
        if ($depth > 5) {
            return $data;
        }

        $redacted = [];

        foreach ($data as $key => $value) {
            $lowercaseKey = strtolower((string) $key);
            $isSensitive = false;

            foreach ($this->sensitiveKeys as $pattern) {
                if (str_contains($lowercaseKey, $pattern)) {
                    $isSensitive = true;
                    break;
                }
            }

            if ($isSensitive) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveData($value, $depth + 1);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    /**
     * Get the current correlation ID.
     */
    protected function getCorrelationId(): string
    {
        return $this->correlationId;
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
