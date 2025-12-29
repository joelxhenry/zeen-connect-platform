<?php

namespace App\Domains\Payment\Gateways\DirectSplit;

use App\Domains\Payment\Contracts\DirectSplitGatewayInterface;
use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\DTOs\RefundResult;
use App\Domains\Payment\DTOs\SplitPaymentData;
use App\Domains\Payment\DTOs\WebhookResult;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Gateways\AbstractGateway;
use App\Domains\Payment\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WiPayDirectSplitGateway extends AbstractGateway implements DirectSplitGatewayInterface
{
    protected array $merchantCredentials;

    protected ?SplitPaymentData $splitData = null;

    public function __construct(array $merchantCredentials = [])
    {
        parent::__construct();
        $this->merchantCredentials = $merchantCredentials;
    }

    public function getProvider(): string
    {
        return 'wipay';
    }

    public function getType(): string
    {
        return GatewayType::SPLIT->value;
    }

    protected function isTestMode(): bool
    {
        return config('services.wipay.test_mode', true);
    }

    protected function getBaseUrl(): string
    {
        return config('services.wipay.api_url');
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer '.config('services.wipay.api_key'),
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->merchantCredentials['account_number']);
    }

    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'TTD', 'USD'];
    }

    public function getPlatformMerchantId(): string
    {
        return config('services.wipay.platform_account_id', '');
    }

    public function configureSplit(Payment $payment, SplitPaymentData $splitData): void
    {
        $this->splitData = $splitData;

        $payment->update([
            'split_details' => $splitData->toArray(),
        ]);
    }

    public function getSplitDetails(string $transactionId): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/transactions/{$transactionId}/splits");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logError('Failed to get split details', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function validateProviderCredentials(array $credentials): bool
    {
        if (empty($credentials['account_number'])) {
            return false;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/accounts/{$credentials['account_number']}/verify");

            return $response->successful() && ($response['valid'] ?? false);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function initializePayment(
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult {
        if (! $this->splitData) {
            return PaymentResult::failure('Split configuration not set');
        }

        $orderId = 'WP-'.strtoupper(Str::random(12));

        // Determine fee structure based on provider settings
        $feeStructure = $payment->processing_fee_payer === 'client' ? 'customer_pay' : 'merchant_absorb';

        try {
            $splits = [
                [
                    'account_number' => $this->getPlatformMerchantId(),
                    'amount' => $this->splitData->platformAmount,
                    'description' => 'Platform fee',
                ],
                [
                    'account_number' => $this->merchantCredentials['account_number'],
                    'amount' => $this->splitData->providerAmount,
                    'description' => 'Provider payment',
                ],
            ];

            $response = Http::asForm()
                ->post($this->baseUrl, [
                    // Required parameters
                    'account_number' => $this->testMode ? '1234567890' : $this->merchantCredentials['account_number'],
                    'country_code' => 'JM',
                    'currency' => $this->splitData->currency ?? 'JMD',
                    'environment' => $this->testMode ? 'sandbox' : 'live',
                    'fee_structure' => $feeStructure,
                    'method' => 'credit_card',
                    'order_id' => $orderId,
                    'origin' => 'zeen_connect',
                    'response_url' => $returnUrl,
                    'total' => $this->formatAmount($this->splitData->totalAmount),
                    // Optional parameters
                    'avs' => 0,
                    'data' => json_encode([
                        'payment_uuid' => $payment->uuid,
                        'splits' => $splits,
                    ]),
                ]);

            if ($response->successful() && isset($response['url'])) {
                $payment->update([
                    'gateway_order_id' => $orderId,
                    'gateway_provider' => $this->getProvider(),
                    'gateway_type' => $this->getType(),
                ]);

                $this->log('Split payment initialized', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                    'splits' => $splits,
                ]);

                return PaymentResult::success(
                    redirectUrl: $response['url'],
                    orderId: $orderId,
                    splitDetails: $splits,
                    rawResponse: $response->json(),
                );
            }

            return PaymentResult::failure(
                $response['message'] ?? 'Payment initialization failed',
                null,
                $response->json()
            );
        } catch (\Exception $e) {
            $this->logError('Split payment initialization error', ['error' => $e->getMessage()]);

            return PaymentResult::failure('Payment service unavailable');
        }
    }

    public function completePayment(Payment $payment, array $callbackData): PaymentResult
    {
        $transactionId = $callbackData['transaction_id'] ?? null;
        $status = $callbackData['status'] ?? null;

        if ($status === 'success' && $transactionId) {
            $splitDetails = $this->getSplitDetails($transactionId);

            $payment->markAsCompleted($transactionId, $callbackData);
            $payment->update([
                'split_transaction_id' => $transactionId,
                'split_details' => array_merge(
                    $payment->split_details ?? [],
                    ['completed_splits' => $splitDetails]
                ),
            ]);

            $this->log('Split payment completed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return PaymentResult::success(
                transactionId: $transactionId,
                splitDetails: $splitDetails
            );
        }

        $payment->markAsFailed($callbackData['message'] ?? 'Payment failed', null, $callbackData);

        return PaymentResult::failure($callbackData['message'] ?? 'Payment failed');
    }

    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        // Split payment refunds may need special handling
        $refundAmount = $amount ?? $payment->amount;

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/refund", [
                    'transaction_id' => $payment->gateway_transaction_id,
                    'amount' => $this->formatAmount($refundAmount),
                ]);

            if ($response->successful() && ($response['status'] ?? '') === 'success') {
                $payment->markAsRefunded();

                return RefundResult::success(
                    $response['refund_id'] ?? '',
                    $refundAmount,
                    $response->json()
                );
            }

            return RefundResult::failure(
                $response['message'] ?? 'Refund failed',
                null,
                $response->json()
            );
        } catch (\Exception $e) {
            $this->logError('Refund error', ['error' => $e->getMessage()]);

            return RefundResult::failure('Refund service unavailable');
        }
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $payload = $request->all();
        $orderId = $payload['order_id'] ?? null;
        $status = $payload['status'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (! $orderId) {
            return WebhookResult::failure('Invalid webhook payload', $payload);
        }

        $payment = Payment::where('gateway_order_id', $orderId)->first();

        if (! $payment) {
            $this->logWarning('Webhook: Payment not found', ['order_id' => $orderId]);

            return WebhookResult::failure('Payment not found', $payload);
        }

        if ($status === 'success' && $transactionId) {
            $splitDetails = $this->getSplitDetails($transactionId);

            $payment->markAsCompleted($transactionId, $payload);
            $payment->update([
                'split_transaction_id' => $transactionId,
                'split_details' => array_merge(
                    $payment->split_details ?? [],
                    ['completed_splits' => $splitDetails]
                ),
            ]);

            return WebhookResult::success($payment->id, $transactionId, 'completed', $payload);
        }

        $payment->markAsFailed($payload['message'] ?? 'Payment failed', null, $payload);

        return WebhookResult::failure($payload['message'] ?? 'Payment failed', $payload);
    }
}
