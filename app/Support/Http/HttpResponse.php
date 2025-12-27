<?php

namespace App\Support\Http;

use App\Support\Http\Exceptions\HttpClientException;
use Illuminate\Http\Client\Response;

class HttpResponse
{
    public function __construct(
        protected Response $response,
        protected string $correlationId,
        protected float $duration,
        protected string $method,
        protected string $url,
    ) {}

    /**
     * Get the correlation ID for this request.
     */
    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * Get the request duration in milliseconds.
     */
    public function duration(): float
    {
        return $this->duration;
    }

    /**
     * Determine if the request was successful.
     */
    public function successful(): bool
    {
        return $this->response->successful();
    }

    /**
     * Determine if the request failed.
     */
    public function failed(): bool
    {
        return $this->response->failed();
    }

    /**
     * Determine if the response indicates a client error.
     */
    public function clientError(): bool
    {
        return $this->response->clientError();
    }

    /**
     * Determine if the response indicates a server error.
     */
    public function serverError(): bool
    {
        return $this->response->serverError();
    }

    /**
     * Get the response status code.
     */
    public function status(): int
    {
        return $this->response->status();
    }

    /**
     * Get the JSON decoded body of the response.
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = $this->response->json();

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key, $default);
    }

    /**
     * Get the body of the response as a string.
     */
    public function body(): string
    {
        return $this->response->body();
    }

    /**
     * Get the response headers.
     */
    public function headers(): array
    {
        return $this->response->headers();
    }

    /**
     * Get a specific header from the response.
     */
    public function header(string $header): ?string
    {
        return $this->response->header($header);
    }

    /**
     * Throw an exception if the response indicates a failure.
     */
    public function throw(): self
    {
        if ($this->failed()) {
            throw HttpClientException::fromResponse(
                response: $this->response,
                correlationId: $this->correlationId,
                method: $this->method,
                url: $this->url,
            );
        }

        return $this;
    }

    /**
     * Execute a callback if the response was successful.
     */
    public function onSuccess(callable $callback): self
    {
        if ($this->successful()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Execute a callback if the response failed.
     */
    public function onFailure(callable $callback): self
    {
        if ($this->failed()) {
            $callback($this);
        }

        return $this;
    }

    /**
     * Get the response as a standardized result array.
     */
    public function toResult(): array
    {
        if ($this->successful()) {
            return [
                'success' => true,
                'data' => $this->json(),
                'correlation_id' => $this->correlationId,
                'duration_ms' => $this->duration,
            ];
        }

        return [
            'success' => false,
            'error' => $this->json('message') ?? $this->json('error') ?? 'Request failed',
            'status' => $this->status(),
            'correlation_id' => $this->correlationId,
            'duration_ms' => $this->duration,
        ];
    }

    /**
     * Get the underlying Laravel HTTP response.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
