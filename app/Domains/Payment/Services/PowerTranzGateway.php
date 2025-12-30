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
    | Tokenization Methods (for Subscriptions)
    |--------------------------------------------------------------------------
    */

    /**
     * Initialize a payment with tokenization for recurring billing.
     * Returns a redirect URL for 3DS authentication.
     */
    public function initializeSubscriptionPayment(
        float $amount,
        string $currency,
        string $orderId,
        string $returnUrl,
        string $email,
        string $name
    ): array {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/sale", [
                    'TransactionIdentifier' => $orderId,
                    'TotalAmount' => $this->formatAmount($amount),
                    'CurrencyCode' => $this->getCurrencyCode($currency),
                    'ThreeDSecure' => true,
                    'Source' => [
                        'CardPan' => '',
                        'CardCvv' => '',
                        'CardExpiration' => '',
                        'CardholderName' => '',
                    ],
                    'OrderIdentifier' => $orderId,
                    'BillingAddress' => [
                        'FirstName' => $name,
                        'EmailAddress' => $email,
                    ],
                    'AddressMatch' => false,
                    'ExtendedData' => [
                        'ThreeDSecure' => [
                            'ChallengeWindowSize' => 5,
                            'ChallengeIndicator' => '01',
                        ],
                        'MerchantResponseUrl' => $returnUrl,
                    ],
                    // Request tokenization for recurring billing
                    'Tokenize' => true,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['RedirectUrl'])) {
                    Log::info('PowerTranz subscription payment initialized', [
                        'order_id' => $orderId,
                    ]);

                    return [
                        'success' => true,
                        'redirect_url' => $data['RedirectUrl'],
                        'spi_token' => $data['SpiToken'] ?? null,
                        'order_id' => $orderId,
                    ];
                }
            }

            Log::error('PowerTranz subscription payment initialization failed', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Payment initialization failed',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz API error during subscription payment init', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.',
            ];
        }
    }

    /**
     * Verify a subscription payment and extract the card token.
     * Call this after 3DS redirect.
     */
    public function verifySubscriptionPayment(string $spiToken): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/payment", [
                    'SpiToken' => $spiToken,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Approved'] ?? false) === true) {
                    return [
                        'success' => true,
                        'transaction_id' => $data['TransactionIdentifier'] ?? null,
                        'order_id' => $data['OrderIdentifier'] ?? null,
                        'token' => $data['Token'] ?? $data['CardToken'] ?? null, // Card token for recurring
                        'card_brand' => $data['CardBrand'] ?? null,
                        'card_last_four' => isset($data['MaskedPan']) ? substr($data['MaskedPan'], -4) : null,
                        'card_expiry' => $data['CardExpiration'] ?? null,
                        'raw_response' => $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['ResponseMessage'] ?? 'Payment verification failed',
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz subscription payment verification error', [
                'spi_token' => substr($spiToken, 0, 10) . '...',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment verification failed. Please contact support.',
            ];
        }
    }

    /**
     * Charge a stored card token for subscription renewal.
     */
    public function chargeToken(
        string $token,
        float $amount,
        string $currency,
        string $orderId,
        ?string $description = null
    ): array {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/sale", [
                    'TransactionIdentifier' => $orderId,
                    'TotalAmount' => $this->formatAmount($amount),
                    'CurrencyCode' => $this->getCurrencyCode($currency),
                    'Source' => [
                        'Token' => $token,
                    ],
                    'OrderIdentifier' => $orderId,
                    'ExtendedData' => [
                        'Description' => $description ?? 'Subscription renewal',
                        'IsRecurring' => true,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (($data['Approved'] ?? false) === true) {
                    Log::info('PowerTranz token charge successful', [
                        'order_id' => $orderId,
                        'transaction_id' => $data['TransactionIdentifier'] ?? null,
                    ]);

                    return [
                        'success' => true,
                        'transaction_id' => $data['TransactionIdentifier'] ?? $orderId,
                        'raw_response' => $data,
                    ];
                }

                return [
                    'success' => false,
                    'error' => $data['ResponseMessage'] ?? 'Payment was declined',
                    'response_code' => $data['ResponseCode'] ?? null,
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Payment processing failed',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz token charge error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.',
            ];
        }
    }

    /**
     * Create a recurring billing profile with PowerTranz.
     * PowerTranz will automatically charge the card based on the schedule.
     *
     * Note: If PowerTranz doesn't support managed recurring profiles,
     * we'll use chargeToken() with a scheduled Laravel job instead.
     */
    public function createRecurringProfile(
        string $token,
        float $amount,
        string $currency,
        string $billingCycle, // 'monthly' or 'annual'
        \DateTime $startDate,
        string $orderId,
        ?string $description = null
    ): array {
        // Determine the billing interval
        $intervalDays = $billingCycle === 'annual' ? 365 : 30;

        try {
            // Attempt to create a recurring profile
            // Note: PowerTranz API endpoints may vary - adjust based on actual API docs
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/recurring/create", [
                    'Token' => $token,
                    'Amount' => $this->formatAmount($amount),
                    'CurrencyCode' => $this->getCurrencyCode($currency),
                    'Frequency' => $billingCycle === 'annual' ? 'yearly' : 'monthly',
                    'StartDate' => $startDate->format('Y-m-d'),
                    'Description' => $description ?? 'Zeen Connect Subscription',
                    'OrderIdentifier' => $orderId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['ProfileId']) || isset($data['RecurringId'])) {
                    $profileId = $data['ProfileId'] ?? $data['RecurringId'];

                    Log::info('PowerTranz recurring profile created', [
                        'profile_id' => $profileId,
                        'order_id' => $orderId,
                    ]);

                    return [
                        'success' => true,
                        'profile_id' => $profileId,
                        'raw_response' => $data,
                    ];
                }
            }

            // If recurring endpoint doesn't exist, return success with null profile_id
            // We'll handle recurring manually via scheduled jobs
            Log::warning('PowerTranz recurring profile endpoint not available, using manual mode', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);

            return [
                'success' => true,
                'profile_id' => null, // Indicates manual recurring mode
                'manual_mode' => true,
                'message' => 'Recurring billing will be handled via scheduled jobs',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz recurring profile creation error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            // Fall back to manual mode
            return [
                'success' => true,
                'profile_id' => null,
                'manual_mode' => true,
                'message' => 'Recurring billing will be handled via scheduled jobs',
            ];
        }
    }

    /**
     * Cancel a recurring billing profile.
     */
    public function cancelRecurringProfile(string $profileId): array
    {
        if (! $profileId) {
            // Manual mode - no profile to cancel
            return [
                'success' => true,
                'message' => 'No recurring profile to cancel (manual mode)',
            ];
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/recurring/cancel", [
                    'ProfileId' => $profileId,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                Log::info('PowerTranz recurring profile cancelled', [
                    'profile_id' => $profileId,
                ]);

                return [
                    'success' => true,
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Failed to cancel recurring profile',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz recurring profile cancellation error', [
                'profile_id' => $profileId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to cancel recurring profile. Please contact support.',
            ];
        }
    }

    /**
     * Update payment method for a recurring profile.
     * Creates a new tokenization flow.
     */
    public function initializePaymentMethodUpdate(
        string $returnUrl,
        string $email,
        string $name,
        string $orderId
    ): array {
        // This is similar to initializeSubscriptionPayment but with $0 amount
        // to just tokenize the card without charging
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/spi/tokenize", [
                    'TransactionIdentifier' => $orderId,
                    'Source' => [
                        'CardPan' => '',
                        'CardCvv' => '',
                        'CardExpiration' => '',
                        'CardholderName' => '',
                    ],
                    'BillingAddress' => [
                        'FirstName' => $name,
                        'EmailAddress' => $email,
                    ],
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

                if (isset($data['RedirectUrl'])) {
                    return [
                        'success' => true,
                        'redirect_url' => $data['RedirectUrl'],
                        'spi_token' => $data['SpiToken'] ?? null,
                        'order_id' => $orderId,
                    ];
                }
            }

            Log::error('PowerTranz payment method update initialization failed', [
                'order_id' => $orderId,
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['Message'] ?? 'Failed to initialize payment method update',
            ];
        } catch (\Exception $e) {
            Log::error('PowerTranz payment method update error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment service unavailable. Please try again.',
            ];
        }
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
