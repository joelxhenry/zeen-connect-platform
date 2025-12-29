<?php

namespace App\Domains\Payment\DTOs;

readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $refundId = null,
        public ?float $refundedAmount = null,
        public ?string $error = null,
        public ?string $errorCode = null,
        public ?array $rawResponse = null,
    ) {}

    public static function success(
        string $refundId,
        ?float $refundedAmount = null,
        ?array $rawResponse = null,
    ): self {
        return new self(
            success: true,
            refundId: $refundId,
            refundedAmount: $refundedAmount,
            rawResponse: $rawResponse,
        );
    }

    public static function failure(
        string $error,
        ?string $errorCode = null,
        ?array $rawResponse = null
    ): self {
        return new self(
            success: false,
            error: $error,
            errorCode: $errorCode,
            rawResponse: $rawResponse,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'refunded_amount' => $this->refundedAmount,
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'raw_response' => $this->rawResponse,
        ];
    }
}
