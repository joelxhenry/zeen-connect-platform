<?php

namespace App\Domains\Payment\Gateways\Escrow;

use App\Domains\Payment\Contracts\EscrowGatewayInterface;
use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\DTOs\RefundResult;
use App\Domains\Payment\DTOs\WebhookResult;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Gateways\AbstractGateway;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\LedgerService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PowerTranzEscrowGateway extends AbstractGateway implements EscrowGatewayInterface
{
    protected string $powerTranzId;

    protected string $password;

    protected LedgerService $ledgerService;

    public function __construct(?LedgerService $ledgerService = null)
    {
        parent::__construct();
        $this->powerTranzId = config('services.powertranz.id', '');
        $this->password = config('services.powertranz.password', '');
        $this->ledgerService = $ledgerService ?? app(LedgerService::class);
    }

    public function getProvider(): string
    {
        return 'powertranz';
    }

    public function getType(): string
    {
        return GatewayType::ESCROW->value;
    }

    protected function isTestMode(): bool
    {
        return config('services.powertranz.test_mode', true);
    }

    protected function getBaseUrl(): string
    {
        return $this->testMode
            ? 'https://staging.ptranz.com/api'
            : 'https://gateway.ptranz.com/api';
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PowerTranz-PowerTranzId' => $this->powerTranzId,
            'PowerTranz-Password' => $this->password,
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->powerTranzId) && ! empty($this->password);
    }

    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'USD', 'TTD', 'BBD', 'XCD'];
    }

    /**
     * Initialize a payment session (SPI - Secure Payment Interface).
     */
    public function initializePayment(
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult {
        $orderId = $this->generateOrderId();

        try {
            // Get client info (support both authenticated and guest)
            $clientName = $payment->client?->name ?? $payment->booking->guest_name ?? 'Guest';
            $clientEmail = $payment->client?->email ?? $payment->booking->guest_email ?? '';

            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/sale", [
                    'TransactionIdentifier' => $orderId,
                    'TotalAmount' => $this->formatAmount($payment->amount),
                    'CurrencyCode' => $this->getCurrencyCode($payment->currency),
                    'ThreeDSecure' => true,
                    'Source' => [
                        'CardPan' => '',
                        'CardCvv' => '',
                        'CardExpiration' => '',
                        'CardholderName' => '',
                    ],
                    'OrderIdentifier' => $payment->uuid,
                    'BillingAddress' => [
                        'FirstName' => $clientName,
                        'EmailAddress' => $clientEmail,
                    ],
                    'AddressMatch' => false,
                    'ExtendedData' => [
                        'ThreeDSecure' => [
                            'ChallengeWindowSize' => 5,
                            'ChallengeIndicator' => '01',
                        ],
                        'MerchantResponseUrl' => $returnUrl,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $payment->update([
                    'gateway_order_id' => $orderId,
                    'status' => PaymentStatus::PROCESSING,
                    'gateway_provider' => $this->getProvider(),
                    'gateway_type' => $this->getType(),
                ]);

                $this->log('Payment initialized', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                ]);

                return PaymentResult::success(
                    redirectUrl: $data['RedirectUrl'] ?? null,
                    spiToken: $data['SpiToken'] ?? null,
                    orderId: $orderId,
                    rawResponse: $data,
                );
            }

            $this->logError('Payment initialization failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);

            return PaymentResult::failure(
                $response->json()['Message'] ?? 'Payment initialization failed',
                null,
                $response->json()
            );
        } catch (RequestException $e) {
            $this->logError('Payment initialization error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure('Payment service unavailable. Please try again.');
        }
    }

    /**
     * Complete/verify a payment after 3DS redirect.
     */
    public function completePayment(Payment $payment, array $callbackData): PaymentResult
    {
        $spiToken = $callbackData['spi_token'] ?? null;

        if (! $spiToken) {
            return PaymentResult::failure('Missing SPI token');
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/payment", [
                    'SpiToken' => $spiToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Approved'] ?? false) === true) {
                    $transactionId = $data['TransactionIdentifier'] ?? null;

                    $payment->markAsCompleted($transactionId, $data);

                    // Update card details
                    if (isset($data['MaskedPan'])) {
                        $payment->update([
                            'card_last_four' => substr($data['MaskedPan'], -4),
                            'card_brand' => $data['CardBrand'] ?? null,
                        ]);
                    }

                    $this->log('Payment completed', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $transactionId,
                    ]);

                    return PaymentResult::success(
                        transactionId: $transactionId,
                        cardDetails: [
                            'last_four' => isset($data['MaskedPan']) ? substr($data['MaskedPan'], -4) : null,
                            'brand' => $data['CardBrand'] ?? null,
                        ],
                        rawResponse: $data,
                    );
                }

                $payment->markAsFailed(
                    $data['ResponseMessage'] ?? 'Payment declined',
                    $data['ResponseCode'] ?? null,
                    $data
                );

                return PaymentResult::failure(
                    $data['ResponseMessage'] ?? 'Payment was declined',
                    $data['ResponseCode'] ?? null,
                    $data
                );
            }

            return PaymentResult::failure(
                $response->json()['Message'] ?? 'Payment verification failed'
            );
        } catch (RequestException $e) {
            $this->logError('Payment completion error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return PaymentResult::failure('Payment verification failed. Please contact support.');
        }
    }

    /**
     * Process a refund.
     */
    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        $refundAmount = $amount ?? $payment->amount;

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/refund", [
                    'TransactionIdentifier' => $this->generateOrderId(),
                    'TotalAmount' => $this->formatAmount($refundAmount),
                    'OriginalTrxnIdentifier' => $payment->gateway_transaction_id,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Approved'] ?? false) === true) {
                    $refundId = $data['TransactionIdentifier'] ?? null;

                    $payment->markAsRefunded();

                    // Also update ledger if this is an escrow payment
                    if ($payment->ledger_entry_id) {
                        $providerRefundAmount = $payment->provider_amount * ($refundAmount / $payment->amount);
                        $this->ledgerService->debitForRefund($payment, $providerRefundAmount);
                    }

                    $this->log('Refund processed', [
                        'payment_id' => $payment->id,
                        'refund_id' => $refundId,
                        'amount' => $refundAmount,
                    ]);

                    return RefundResult::success(
                        $refundId,
                        $refundAmount,
                        $data
                    );
                }

                return RefundResult::failure(
                    $data['ResponseMessage'] ?? 'Refund was not approved',
                    $data['ResponseCode'] ?? null,
                    $data
                );
            }

            return RefundResult::failure(
                $response->json()['Message'] ?? 'Refund failed'
            );
        } catch (RequestException $e) {
            $this->logError('Refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return RefundResult::failure('Refund service unavailable. Please try again.');
        }
    }

    /**
     * Handle webhook notification.
     */
    public function handleWebhook(Request $request): WebhookResult
    {
        $payload = $request->all();
        $orderIdentifier = $payload['OrderIdentifier'] ?? null;
        $approved = $payload['Approved'] ?? false;
        $transactionId = $payload['TransactionIdentifier'] ?? null;

        if (! $orderIdentifier) {
            return WebhookResult::failure('Invalid webhook payload', $payload);
        }

        $payment = Payment::where('uuid', $orderIdentifier)->first();

        if (! $payment) {
            $this->logWarning('Webhook: Payment not found', ['order_id' => $orderIdentifier]);

            return WebhookResult::failure('Payment not found', $payload);
        }

        if ($approved && $transactionId) {
            $payment->markAsCompleted($transactionId, $payload);

            // Update card details if available
            if (isset($payload['MaskedPan'])) {
                $payment->update([
                    'card_last_four' => substr($payload['MaskedPan'], -4),
                    'card_brand' => $payload['CardBrand'] ?? null,
                ]);
            }

            $this->log('Webhook: Payment completed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return WebhookResult::success(
                $payment->id,
                $transactionId,
                'completed',
                $payload
            );
        }

        $payment->markAsFailed(
            $payload['ResponseMessage'] ?? 'Payment declined',
            $payload['ResponseCode'] ?? null,
            $payload
        );

        return WebhookResult::failure(
            $payload['ResponseMessage'] ?? 'Payment declined',
            $payload
        );
    }

    /**
     * Record a ledger entry for an escrowed payment.
     */
    public function recordToLedger(Payment $payment): LedgerEntry
    {
        return $this->ledgerService->creditProvider($payment);
    }

    /**
     * Generate a unique order ID.
     */
    private function generateOrderId(): string
    {
        return 'ZC-'.strtoupper(Str::random(12));
    }
}
