<?php

namespace App\Domains\Payment\Services;

use App\Domains\Payment\Enums\PaymentStatus;
use App\Domains\Payment\Models\Payment;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PowerTranzGateway
{
    private string $baseUrl;
    private string $powerTranzId;
    private string $password;
    private bool $testMode;

    public function __construct()
    {
        $this->testMode = config('services.powertranz.test_mode', true);
        $this->baseUrl = $this->testMode
            ? 'https://staging.ptranz.com/api'
            : 'https://gateway.ptranz.com/api';
        $this->powerTranzId = config('services.powertranz.id', '');
        $this->password = config('services.powertranz.password', '');
    }

    /**
     * Initialize a payment session (SPI - Secure Payment Interface).
     * Returns a redirect URL for 3DS authentication.
     */
    public function initializePayment(Payment $payment, string $returnUrl, string $cancelUrl): array
    {
        $orderId = $this->generateOrderId();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/sale", [
                    'TransactionIdentifier' => $orderId,
                    'TotalAmount' => $this->formatAmount($payment->amount),
                    'CurrencyCode' => $this->getCurrencyCode($payment->currency),
                    'ThreeDSecure' => true,
                    'Source' => [
                        'CardPan' => '', // Will be collected on hosted page
                        'CardCvv' => '',
                        'CardExpiration' => '',
                        'CardholderName' => '',
                    ],
                    'OrderIdentifier' => $payment->uuid,
                    'BillingAddress' => [
                        'FirstName' => $payment->client->name,
                        'EmailAddress' => $payment->client->email,
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

                // Update payment with order ID
                $payment->update([
                    'gateway_order_id' => $orderId,
                    'status' => PaymentStatus::PROCESSING,
                ]);

                return [
                    'success' => true,
                    'redirect_url' => $data['RedirectUrl'] ?? null,
                    'spi_token' => $data['SpiToken'] ?? null,
                    'order_id' => $orderId,
                ];
            }

            Log::error('PowerTranz payment initialization failed', [
                'payment_id' => $payment->id,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Payment initialization failed',
            ];
        } catch (RequestException $e) {
            Log::error('PowerTranz API error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.',
            ];
        }
    }

    /**
     * Process a direct card payment (for testing/non-3DS).
     */
    public function processDirectPayment(
        Payment $payment,
        string $cardNumber,
        string $cvv,
        string $expiry,
        string $cardholderName
    ): array {
        $orderId = $this->generateOrderId();

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/sale", [
                    'TransactionIdentifier' => $orderId,
                    'TotalAmount' => $this->formatAmount($payment->amount),
                    'CurrencyCode' => $this->getCurrencyCode($payment->currency),
                    'Source' => [
                        'CardPan' => $cardNumber,
                        'CardCvv' => $cvv,
                        'CardExpiration' => $expiry,
                        'CardholderName' => $cardholderName,
                    ],
                    'OrderIdentifier' => $payment->uuid,
                    'BillingAddress' => [
                        'FirstName' => $payment->client->name,
                        'EmailAddress' => $payment->client->email,
                    ],
                ]);

            return $this->handlePaymentResponse($payment, $orderId, $response);
        } catch (RequestException $e) {
            Log::error('PowerTranz direct payment error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            $payment->markAsFailed('Payment service unavailable');

            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.',
            ];
        }
    }

    /**
     * Verify a payment after 3DS redirect.
     */
    public function verifyPayment(string $spiToken): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/payment", [
                    'SpiToken' => $spiToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => ($data['Approved'] ?? false) === true,
                    'transaction_id' => $data['TransactionIdentifier'] ?? null,
                    'order_id' => $data['OrderIdentifier'] ?? null,
                    'response_code' => $data['ResponseCode'] ?? null,
                    'card_brand' => $data['CardBrand'] ?? null,
                    'card_last_four' => $data['MaskedPan'] ? substr($data['MaskedPan'], -4) : null,
                    'raw_response' => $data,
                    'error' => $data['ResponseMessage'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Payment verification failed',
            ];
        } catch (RequestException $e) {
            Log::error('PowerTranz payment verification error', [
                'spi_token' => $spiToken,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed. Please contact support.',
            ];
        }
    }

    /**
     * Process a refund.
     */
    public function refund(Payment $payment, ?float $amount = null): array
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
                    $payment->markAsRefunded();

                    return [
                        'success' => true,
                        'refund_id' => $data['TransactionIdentifier'] ?? null,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['ResponseMessage'] ?? 'Refund was not approved',
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Refund failed',
            ];
        } catch (RequestException $e) {
            Log::error('PowerTranz refund error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Refund service unavailable. Please try again.',
            ];
        }
    }

    /**
     * Handle webhook/callback from PowerTranz.
     */
    public function handleWebhook(array $payload): array
    {
        $orderIdentifier = $payload['OrderIdentifier'] ?? null;
        $approved = $payload['Approved'] ?? false;
        $transactionId = $payload['TransactionIdentifier'] ?? null;

        if (! $orderIdentifier) {
            return ['success' => false, 'error' => 'Invalid webhook payload'];
        }

        $payment = Payment::where('uuid', $orderIdentifier)->first();

        if (! $payment) {
            Log::warning('PowerTranz webhook: Payment not found', ['order_id' => $orderIdentifier]);

            return ['success' => false, 'error' => 'Payment not found'];
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

            return ['success' => true, 'payment' => $payment];
        }

        $payment->markAsFailed(
            $payload['ResponseMessage'] ?? 'Payment declined',
            $payload['ResponseCode'] ?? null,
            $payload
        );

        return ['success' => false, 'error' => $payload['ResponseMessage'] ?? 'Payment declined'];
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helper Methods
    |--------------------------------------------------------------------------
    */

    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'PowerTranz-PowerTranzId' => $this->powerTranzId,
            'PowerTranz-Password' => $this->password,
        ];
    }

    private function generateOrderId(): string
    {
        return 'ZC-' . strtoupper(Str::random(12));
    }

    private function formatAmount(float $amount): float
    {
        return round($amount, 2);
    }

    private function getCurrencyCode(string $currency): string
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

    private function handlePaymentResponse(Payment $payment, string $orderId, $response): array
    {
        if ($response->successful()) {
            $data = $response->json();

            if (($data['Approved'] ?? false) === true) {
                $payment->markAsCompleted(
                    $data['TransactionIdentifier'] ?? $orderId,
                    $data
                );

                // Store card details
                if (isset($data['MaskedPan'])) {
                    $payment->update([
                        'gateway_order_id' => $orderId,
                        'card_last_four' => substr($data['MaskedPan'], -4),
                        'card_brand' => $data['CardBrand'] ?? null,
                    ]);
                }

                return [
                    'success' => true,
                    'transaction_id' => $data['TransactionIdentifier'] ?? $orderId,
                ];
            }

            $payment->markAsFailed(
                $data['ResponseMessage'] ?? 'Payment declined',
                $data['ResponseCode'] ?? null,
                $data
            );

            return [
                'success' => false,
                'error' => $data['ResponseMessage'] ?? 'Payment was declined',
            ];
        }

        $payment->markAsFailed(
            $response->json()['Message'] ?? 'Payment processing failed',
            null,
            $response->json()
        );

        return [
            'success' => false,
            'error' => $response->json()['Message'] ?? 'Payment processing failed',
        ];
    }
}
