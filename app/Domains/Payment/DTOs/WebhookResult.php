<?php

namespace App\Domains\Payment\DTOs;

readonly class WebhookResult
{
    public function __construct(
        public bool $success,
        public ?int $paymentId = null,
        public ?string $transactionId = null,
        public ?string $status = null,
        public ?string $error = null,
        public ?array $rawPayload = null,
    ) {}

    public static function success(
        int $paymentId,
        ?string $transactionId = null,
        ?string $status = null,
        ?array $rawPayload = null,
    ): self {
        return new self(
            success: true,
            paymentId: $paymentId,
            transactionId: $transactionId,
            status: $status,
            rawPayload: $rawPayload,
        );
    }

    public static function failure(
        string $error,
        ?array $rawPayload = null
    ): self {
        return new self(
            success: false,
            error: $error,
            rawPayload: $rawPayload,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_id' => $this->paymentId,
            'transaction_id' => $this->transactionId,
            'status' => $this->status,
            'error' => $this->error,
        ];
    }
}
