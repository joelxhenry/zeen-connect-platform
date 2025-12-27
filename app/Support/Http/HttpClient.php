<?php

namespace App\Support\Http;

use App\Support\Http\Exceptions\HttpClientException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Sentry\State\Scope;

use function Sentry\captureException;
use function Sentry\withScope;

class HttpClient
{
    protected HttpClientConfig $config;

    protected string $correlationId;

    public function __construct(?HttpClientConfig $config = null)
    {
        $this->config = $config ?? HttpClientConfig::fromConfig();
        $this->correlationId = $this->config->correlationId ?? $this->generateCorrelationId();
    }

    /**
     * Create a new HTTP client instance.
     */
    public static function create(?HttpClientConfig $config = null): self
    {
        return new self($config);
    }

    /**
     * Create an HTTP client configured for payment gateways.
     */
    public static function forPayments(?string $provider = null): self
    {
        $config = HttpClientConfig::fromConfig('payments');

        if ($provider) {
            $baseUrl = match ($provider) {
                'powertranz' => config('services.powertranz.test_mode', true)
                    ? 'https://staging.ptranz.com/api'
                    : 'https://gateway.ptranz.com/api',
                'wipay' => config('services.wipay.test_mode', true)
                    ? 'https://sandbox.wipayfinancial.com/v1'
                    : 'https://api.wipayfinancial.com/v1',
                'fygaro' => config('services.fygaro.test_mode', true)
                    ? 'https://sandbox.fygaro.com/api/v1'
                    : 'https://api.fygaro.com/api/v1',
                default => null,
            };

            if ($baseUrl) {
                $config = $config->merge(['baseUrl' => $baseUrl]);
            }
        }

        return new self($config);
    }

    /**
     * Create an HTTP client with a specific preset.
     */
    public static function withPreset(string $preset): self
    {
        return new self(HttpClientConfig::fromConfig($preset));
    }

    // =========================================================================
    // Fluent Configuration Methods
    // =========================================================================

    /**
     * Set the base URL for requests.
     */
    public function withBaseUrl(string $baseUrl): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge(['baseUrl' => $baseUrl]);

        return $clone;
    }

    /**
     * Add headers to requests.
     */
    public function withHeaders(array $headers): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge([
            'headers' => array_merge($this->config->headers, $headers),
        ]);

        return $clone;
    }

    /**
     * Set the request timeout.
     */
    public function withTimeout(int $seconds): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge(['timeout' => $seconds]);

        return $clone;
    }

    /**
     * Configure retry behavior.
     */
    public function withRetries(int $attempts, ?array $delays = null): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge([
            'retryAttempts' => $attempts,
            'retryDelays' => $delays ?? $this->generateExponentialDelays($attempts),
        ]);

        return $clone;
    }

    /**
     * Disable retries.
     */
    public function withoutRetries(): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge([
            'retryAttempts' => 1,
            'retryOnConnectionError' => false,
        ]);

        return $clone;
    }

    /**
     * Set the log channel.
     */
    public function withLogChannel(string $channel): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge(['logChannel' => $channel]);

        return $clone;
    }

    /**
     * Disable logging.
     */
    public function withoutLogging(): self
    {
        $clone = clone $this;
        $clone->config = $this->config->merge(['loggingEnabled' => false]);

        return $clone;
    }

    /**
     * Set a custom correlation ID.
     */
    public function withCorrelationId(string $correlationId): self
    {
        $clone = clone $this;
        $clone->correlationId = $correlationId;

        return $clone;
    }

    // =========================================================================
    // HTTP Methods
    // =========================================================================

    /**
     * Send a GET request.
     */
    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->request('GET', $url, ['query' => $query]);
    }

    /**
     * Send a POST request.
     */
    public function post(string $url, array $data = []): HttpResponse
    {
        return $this->request('POST', $url, ['json' => $data]);
    }

    /**
     * Send a PUT request.
     */
    public function put(string $url, array $data = []): HttpResponse
    {
        return $this->request('PUT', $url, ['json' => $data]);
    }

    /**
     * Send a PATCH request.
     */
    public function patch(string $url, array $data = []): HttpResponse
    {
        return $this->request('PATCH', $url, ['json' => $data]);
    }

    /**
     * Send a DELETE request.
     */
    public function delete(string $url, array $data = []): HttpResponse
    {
        return $this->request('DELETE', $url, ['json' => $data]);
    }

    // =========================================================================
    // Core Request Logic
    // =========================================================================

    /**
     * Execute an HTTP request with retries and logging.
     */
    protected function request(string $method, string $url, array $options = []): HttpResponse
    {
        $fullUrl = $this->buildUrl($url);
        $startTime = microtime(true);
        $attempt = 0;
        $lastException = null;

        $this->logRequest($method, $fullUrl, $options);

        while ($attempt < $this->config->retryAttempts) {
            $attempt++;

            try {
                $response = $this->executeRequest($method, $fullUrl, $options);
                $duration = (microtime(true) - $startTime) * 1000;

                $this->logResponse($method, $fullUrl, $response, $duration, $attempt);

                // Check if we should retry based on status code
                if ($this->shouldRetry($response, $attempt)) {
                    $this->waitBeforeRetry($attempt);

                    continue;
                }

                return new HttpResponse(
                    response: $response,
                    correlationId: $this->correlationId,
                    duration: $duration,
                    method: $method,
                    url: $fullUrl,
                );
            } catch (ConnectionException $e) {
                $lastException = $e;
                $duration = (microtime(true) - $startTime) * 1000;

                $this->logConnectionError($method, $fullUrl, $e, $duration, $attempt);

                if (! $this->config->retryOnConnectionError || $attempt >= $this->config->retryAttempts) {
                    $this->reportToSentry($e, $method, $fullUrl);

                    throw HttpClientException::connectionFailed(
                        correlationId: $this->correlationId,
                        method: $method,
                        url: $fullUrl,
                        previous: $e,
                        context: ['duration_ms' => $duration, 'attempts' => $attempt],
                    );
                }

                $this->waitBeforeRetry($attempt);
            }
        }

        // Max retries exceeded
        $this->reportToSentry(
            $lastException ?? new \RuntimeException('Max retries exceeded'),
            $method,
            $fullUrl
        );

        throw HttpClientException::maxRetriesExceeded(
            correlationId: $this->correlationId,
            method: $method,
            url: $fullUrl,
            attempts: $attempt,
            previous: $lastException,
        );
    }

    /**
     * Execute a single HTTP request.
     */
    protected function executeRequest(string $method, string $url, array $options): Response
    {
        $pending = Http::withHeaders(array_merge(
            $this->config->headers,
            ['X-Correlation-ID' => $this->correlationId]
        ))
            ->timeout($this->config->timeout)
            ->connectTimeout($this->config->connectTimeout);

        return match (strtoupper($method)) {
            'GET' => $pending->get($url, $options['query'] ?? []),
            'POST' => $pending->post($url, $options['json'] ?? []),
            'PUT' => $pending->put($url, $options['json'] ?? []),
            'PATCH' => $pending->patch($url, $options['json'] ?? []),
            'DELETE' => $pending->delete($url, $options['json'] ?? []),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };
    }

    // =========================================================================
    // Retry Logic
    // =========================================================================

    /**
     * Determine if the request should be retried.
     */
    protected function shouldRetry(Response $response, int $attempt): bool
    {
        if ($attempt >= $this->config->retryAttempts) {
            return false;
        }

        return in_array($response->status(), $this->config->retryableStatusCodes);
    }

    /**
     * Wait before retrying a request.
     */
    protected function waitBeforeRetry(int $attempt): void
    {
        $delay = $this->config->getDelayForAttempt($attempt - 1);
        usleep($delay * 1000); // Convert milliseconds to microseconds
    }

    /**
     * Generate exponential backoff delays.
     */
    protected function generateExponentialDelays(int $attempts): array
    {
        $delays = [];
        for ($i = 0; $i < $attempts; $i++) {
            $delays[] = min(100 * pow(2, $i), 5000);
        }

        return $delays;
    }

    // =========================================================================
    // Logging
    // =========================================================================

    /**
     * Log an outgoing request.
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        Log::channel($this->config->logChannel)->info('HTTP Request', [
            'correlation_id' => $this->correlationId,
            'method' => $method,
            'url' => $url,
            'headers' => $this->redactSensitiveHeaders($this->config->headers),
            'body' => $this->redactSensitiveData($options['json'] ?? $options['query'] ?? []),
        ]);
    }

    /**
     * Log a response.
     */
    protected function logResponse(string $method, string $url, Response $response, float $duration, int $attempt): void
    {
        if (! $this->config->loggingEnabled) {
            return;
        }

        $level = $response->successful() ? 'info' : 'warning';

        Log::channel($this->config->logChannel)->{$level}('HTTP Response', [
            'correlation_id' => $this->correlationId,
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => round($duration, 2),
            'attempt' => $attempt,
            'body' => $this->redactSensitiveData($response->json() ?? []),
        ]);
    }

    /**
     * Log a connection error.
     */
    protected function logConnectionError(string $method, string $url, ConnectionException $e, float $duration, int $attempt): void
    {
        Log::channel($this->config->logChannel)->error('HTTP Connection Error', [
            'correlation_id' => $this->correlationId,
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'duration_ms' => round($duration, 2),
            'attempt' => $attempt,
            'will_retry' => $attempt < $this->config->retryAttempts && $this->config->retryOnConnectionError,
        ]);
    }

    // =========================================================================
    // Sensitive Data Redaction
    // =========================================================================

    /**
     * Redact sensitive values from headers.
     */
    protected function redactSensitiveHeaders(array $headers): array
    {
        $redacted = [];

        foreach ($headers as $key => $value) {
            $lowercaseKey = strtolower($key);
            $isSensitive = false;

            foreach ($this->config->sensitiveHeaders as $pattern) {
                if (str_contains($lowercaseKey, strtolower($pattern))) {
                    $isSensitive = true;

                    break;
                }
            }

            $redacted[$key] = $isSensitive ? '[REDACTED]' : $value;
        }

        return $redacted;
    }

    /**
     * Recursively redact sensitive values from data.
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

            foreach ($this->config->sensitiveBodyKeys as $pattern) {
                if (str_contains($lowercaseKey, strtolower($pattern))) {
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

    // =========================================================================
    // Error Reporting
    // =========================================================================

    /**
     * Report an error to Sentry.
     */
    protected function reportToSentry(\Throwable $e, string $method, string $url): void
    {
        if (! app()->bound('sentry') || ! app()->environment('production')) {
            return;
        }

        withScope(function (Scope $scope) use ($e, $method, $url): void {
            $scope->setContext('http_request', [
                'correlation_id' => $this->correlationId,
                'method' => $method,
                'url' => $url,
                'headers' => $this->redactSensitiveHeaders($this->config->headers),
                'timeout' => $this->config->timeout,
                'retry_attempts' => $this->config->retryAttempts,
            ]);
            $scope->setTag('http_client_error', 'true');
            $scope->setTag('http_method', $method);

            captureException($e);
        });
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Build the full URL for a request.
     */
    protected function buildUrl(string $url): string
    {
        // If the URL is already absolute, return it as-is
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Prepend the base URL
        $baseUrl = rtrim($this->config->baseUrl ?? '', '/');
        $path = ltrim($url, '/');

        return $baseUrl ? "{$baseUrl}/{$path}" : $path;
    }

    /**
     * Generate a unique correlation ID.
     */
    protected function generateCorrelationId(): string
    {
        return 'req_' . Str::random(16);
    }

    /**
     * Get the current correlation ID.
     */
    public function getCorrelationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Get the current configuration.
     */
    public function getConfig(): HttpClientConfig
    {
        return $this->config;
    }
}
