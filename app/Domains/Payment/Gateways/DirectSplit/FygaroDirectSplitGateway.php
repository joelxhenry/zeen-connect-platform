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

class FygaroDirectSplitGateway extends AbstractGateway implements DirectSplitGatewayInterface
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
        return 'fygaro';
    }

    public function getType(): string
    {
        return GatewayType::SPLIT->value;
    }

    protected function isTestMode(): bool
    {
        return config('services.fygaro.test_mode', true);
    }

    protected function getBaseUrl(): string
    {
        return $this->testMode
            ? 'https://sandbox.fygaro.com/api/v1'
            : 'https://api.fygaro.com/api/v1';
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'X-API-Key' => config('services.fygaro.api_key'),
            'X-Merchant-ID' => config('services.fygaro.merchant_id'),
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->merchantCredentials['merchant_id']);
    }

    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'USD'];
    }

    public function getPlatformMerchantId(): string
    {
        return config('services.fygaro.merchant_id', '');
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
                ->get("{$this->baseUrl}/payments/{$transactionId}/splits");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            $this->logError('Failed to get split details', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function validateProviderCredentials(array $credentials): bool
    {
        if (empty($credentials['merchant_id'])) {
            return false;
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/merchants/{$credentials['merchant_id']}/verify");

            return $response->successful() && ($response['verified'] ?? false);
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

        $orderId = 'FG-'.strtoupper(Str::random(12));

        try {
            $splits = [
                [
                    'merchant_id' => $this->getPlatformMerchantId(),
                    'amount' => $this->splitData->platformAmount,
                    'description' => 'Platform fee',
                ],
                [
                    'merchant_id' => $this->merchantCredentials['merchant_id'],
                    'amount' => $this->splitData->providerAmount,
                    'description' => 'Provider payment',
                ],
            ];

            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/payments/split", [
                    'amount' => $this->splitData->totalAmount,
                    'currency' => $this->splitData->currency,
                    'order_id' => $orderId,
                    'description' => "Payment for booking #{$payment->booking->uuid}",
                    'success_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'webhook_url' => route('webhooks.fygaro'),
                    'splits' => $splits,
                ]);

            if ($response->successful() && isset($response['checkout_url'])) {
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
                    redirectUrl: $response['checkout_url'],
                    orderId: $orderId,
                    splitDetails: $splits,
                    rawResponse: $response->json(),
                );
            }

            return PaymentResult::failure(
                $response['error'] ?? 'Payment initialization failed',
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

        if ($status === 'completed' && $transactionId) {
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

        $payment->markAsFailed($callbackData['error'] ?? 'Payment failed', null, $callbackData);

        return PaymentResult::failure($callbackData['error'] ?? 'Payment failed');
    }

    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        $refundAmount = $amount ?? $payment->amount;

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/refunds/create", [
                    'transaction_id' => $payment->gateway_transaction_id,
                    'amount' => $this->formatAmount($refundAmount),
                    'reason' => 'Customer refund request',
                ]);

            if ($response->successful() && ($response['status'] ?? '') === 'approved') {
                $payment->markAsRefunded();

                return RefundResult::success(
                    $response['refund_id'] ?? '',
                    $refundAmount,
                    $response->json()
                );
            }

            return RefundResult::failure(
                $response['error'] ?? 'Refund failed',
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

        if ($status === 'completed' && $transactionId) {
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

        $payment->markAsFailed($payload['error'] ?? 'Payment failed', null, $payload);

        return WebhookResult::failure($payload['error'] ?? 'Payment failed', $payload);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $signature = $request->header('X-Fygaro-Signature');

        if (! $signature) {
            return false;
        }

        $secretKey = config('services.fygaro.secret_key');
        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secretKey);

        return hash_equals($expectedSignature, $signature);
    }
}
