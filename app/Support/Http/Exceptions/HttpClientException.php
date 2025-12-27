<?php

namespace App\Support\Http\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;
use Throwable;

class HttpClientException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $correlationId,
        public readonly string $method,
        public readonly string $url,
        public readonly ?int $statusCode = null,
        public readonly ?array $responseBody = null,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }

    /**
     * Create an exception from an HTTP response.
     */
    public static function fromResponse(
        Response $response,
        string $correlationId,
        string $method,
        string $url,
        array $context = []
    ): self {
        $statusCode = $response->status();
        $responseBody = $response->json();

        $message = self::extractErrorMessage($response) ?? "HTTP request failed with status {$statusCode}";

        return new self(
            message: $message,
            correlationId: $correlationId,
            method: $method,
            url: $url,
            statusCode: $statusCode,
            responseBody: $responseBody,
            context: $context,
        );
    }

    /**
     * Create an exception for a connection failure.
     */
    public static function connectionFailed(
        string $correlationId,
        string $method,
        string $url,
        Throwable $previous,
        array $context = []
    ): self {
        return new self(
            message: 'Connection failed: ' . $previous->getMessage(),
            correlationId: $correlationId,
            method: $method,
            url: $url,
            statusCode: null,
            responseBody: null,
            context: $context,
            previous: $previous,
        );
    }

    /**
     * Create an exception for max retries exceeded.
     */
    public static function maxRetriesExceeded(
        string $correlationId,
        string $method,
        string $url,
        int $attempts,
        ?Throwable $previous = null,
        array $context = []
    ): self {
        return new self(
            message: "Max retries ({$attempts}) exceeded for HTTP request",
            correlationId: $correlationId,
            method: $method,
            url: $url,
            statusCode: null,
            responseBody: null,
            context: array_merge($context, ['attempts' => $attempts]),
            previous: $previous,
        );
    }

    /**
     * Extract error message from response.
     */
    protected static function extractErrorMessage(Response $response): ?string
    {
        $body = $response->json();

        if (! is_array($body)) {
            return null;
        }

        // Common error message field names used by various APIs
        $messageFields = [
            'message',
            'error',
            'error_message',
            'errorMessage',
            'Message',
            'ResponseMessage',
            'error.message',
        ];

        foreach ($messageFields as $field) {
            if (str_contains($field, '.')) {
                $value = data_get($body, $field);
            } else {
                $value = $body[$field] ?? null;
            }

            if (is_string($value) && ! empty($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get context for Sentry reporting.
     */
    public function toSentryContext(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'method' => $this->method,
            'url' => $this->url,
            'status_code' => $this->statusCode,
            'response_body' => $this->responseBody,
            'context' => $this->context,
        ];
    }

    /**
     * Check if this is a connection error.
     */
    public function isConnectionError(): bool
    {
        return $this->statusCode === null && $this->getPrevious() !== null;
    }

    /**
     * Check if this is a server error.
     */
    public function isServerError(): bool
    {
        return $this->statusCode !== null && $this->statusCode >= 500;
    }

    /**
     * Check if this is a client error.
     */
    public function isClientError(): bool
    {
        return $this->statusCode !== null && $this->statusCode >= 400 && $this->statusCode < 500;
    }
}
