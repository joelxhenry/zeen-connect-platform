<?php

namespace App\Domains\Payment\DTOs;

readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public ?string $redirectUrl = null,
        public ?string $transactionId = null,
        public ?string $spiToken = null,
        public ?string $orderId = null,
        public ?string $error = null,
        public ?string $errorCode = null,
        public ?array $rawResponse = null,
        public ?array $cardDetails = null,
        public ?array $splitDetails = null,
    ) {}

    public static function success(
        ?string $redirectUrl = null,
        ?string $transactionId = null,
        ?string $spiToken = null,
        ?string $orderId = null,
        ?array $cardDetails = null,
        ?array $splitDetails = null,
        ?array $rawResponse = null,
    ): self {
        return new self(
            success: true,
            redirectUrl: $redirectUrl,
            transactionId: $transactionId,
            spiToken: $spiToken,
            orderId: $orderId,
            cardDetails: $cardDetails,
            splitDetails: $splitDetails,
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
            'redirect_url' => $this->redirectUrl,
            'transaction_id' => $this->transactionId,
            'spi_token' => $this->spiToken,
            'order_id' => $this->orderId,
            'error' => $this->error,
            'error_code' => $this->errorCode,
            'card_details' => $this->cardDetails,
            'split_details' => $this->splitDetails,
        ];
    }
}
