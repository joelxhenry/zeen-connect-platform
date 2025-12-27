<?php

namespace App\Support\Http;

readonly class HttpClientConfig
{
    public function __construct(
        public ?string $baseUrl = null,
        public array $headers = [],
        public int $timeout = 30,
        public int $connectTimeout = 10,
        public int $retryAttempts = 3,
        public array $retryDelays = [100, 500, 1000],
        public array $retryableStatusCodes = [429, 500, 502, 503, 504],
        public bool $retryOnConnectionError = true,
        public string $logChannel = 'stack',
        public bool $loggingEnabled = true,
        public array $sensitiveHeaders = [],
        public array $sensitiveBodyKeys = [],
        public ?string $correlationId = null,
    ) {}

    /**
     * Create a config instance from the configuration file.
     */
    public static function fromConfig(string $preset = 'default'): self
    {
        $defaults = config('http-client.default', []);
        $sensitiveHeaders = config('http-client.sensitive_headers', []);
        $sensitiveBodyKeys = config('http-client.sensitive_body_keys', []);

        // If a preset is specified, merge it with defaults
        $presetConfig = [];
        if ($preset !== 'default') {
            $presetConfig = config("http-client.presets.{$preset}", []);
        }

        $merged = array_merge($defaults, $presetConfig);

        return new self(
            baseUrl: null,
            headers: [],
            timeout: $merged['timeout'] ?? 30,
            connectTimeout: $merged['connect_timeout'] ?? 10,
            retryAttempts: $merged['retry_attempts'] ?? 3,
            retryDelays: $merged['retry_delays'] ?? [100, 500, 1000],
            retryableStatusCodes: $merged['retryable_status_codes'] ?? [429, 500, 502, 503, 504],
            retryOnConnectionError: $merged['retry_on_connection_error'] ?? true,
            logChannel: $merged['log_channel'] ?? 'stack',
            loggingEnabled: $merged['logging_enabled'] ?? true,
            sensitiveHeaders: $sensitiveHeaders,
            sensitiveBodyKeys: $sensitiveBodyKeys,
            correlationId: null,
        );
    }

    /**
     * Create a new config instance with merged overrides.
     */
    public function merge(array $overrides): self
    {
        return new self(
            baseUrl: $overrides['baseUrl'] ?? $this->baseUrl,
            headers: $overrides['headers'] ?? $this->headers,
            timeout: $overrides['timeout'] ?? $this->timeout,
            connectTimeout: $overrides['connectTimeout'] ?? $this->connectTimeout,
            retryAttempts: $overrides['retryAttempts'] ?? $this->retryAttempts,
            retryDelays: $overrides['retryDelays'] ?? $this->retryDelays,
            retryableStatusCodes: $overrides['retryableStatusCodes'] ?? $this->retryableStatusCodes,
            retryOnConnectionError: $overrides['retryOnConnectionError'] ?? $this->retryOnConnectionError,
            logChannel: $overrides['logChannel'] ?? $this->logChannel,
            loggingEnabled: $overrides['loggingEnabled'] ?? $this->loggingEnabled,
            sensitiveHeaders: $overrides['sensitiveHeaders'] ?? $this->sensitiveHeaders,
            sensitiveBodyKeys: $overrides['sensitiveBodyKeys'] ?? $this->sensitiveBodyKeys,
            correlationId: $overrides['correlationId'] ?? $this->correlationId,
        );
    }

    /**
     * Check if retries are enabled.
     */
    public function hasRetries(): bool
    {
        return $this->retryAttempts > 1;
    }

    /**
     * Get the delay for a specific retry attempt (0-indexed).
     */
    public function getDelayForAttempt(int $attempt): int
    {
        if (empty($this->retryDelays)) {
            return 100;
        }

        return $this->retryDelays[$attempt] ?? end($this->retryDelays);
    }
}
