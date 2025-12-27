<?php

namespace App\Domains\Payment\Gateways\Escrow;

use App\Domains\Payment\Contracts\EscrowGatewayInterface;
use App\Domains\Payment\DTOs\PaymentResult;
use App\Domains\Payment\DTOs\RefundResult;
use App\Domains\Payment\DTOs\WebhookResult;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Gateways\AbstractGateway;
use App\Domains\Payment\Models\LedgerEntry;
use App\Domains\Payment\Models\Payment;
use App\Domains\Payment\Services\LedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FygaroEscrowGateway extends AbstractGateway implements EscrowGatewayInterface
{
    protected LedgerService $ledgerService;

    protected string $merchantId;

    protected string $apiKey;

    protected string $secretKey;

    public function __construct(?LedgerService $ledgerService = null)
    {
        parent::__construct();
        $this->ledgerService = $ledgerService ?? app(LedgerService::class);
        $this->merchantId = config('services.fygaro.merchant_id', '');
        $this->apiKey = config('services.fygaro.api_key', '');
        $this->secretKey = config('services.fygaro.secret_key', '');
    }

    public function getProvider(): string
    {
        return 'fygaro';
    }

    public function getType(): string
    {
        return GatewayType::ESCROW->value;
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
            'X-API-Key' => $this->apiKey,
            'X-Merchant-ID' => $this->merchantId,
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->merchantId) && ! empty($this->apiKey);
    }

    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'USD'];
    }

    public function initializePayment(
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult {
        $orderId = 'FG-'.strtoupper(Str::random(12));

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post("{$this->baseUrl}/payments/create", [
                    'merchant_id' => $this->merchantId,
                    'amount' => $this->formatAmount($payment->amount),
                    'currency' => $payment->currency,
                    'order_id' => $orderId,
                    'description' => "Payment for booking #{$payment->booking->uuid}",
                    'success_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                    'webhook_url' => route('webhooks.fygaro'),
                ]);

            if ($response->successful() && isset($response['checkout_url'])) {
                $payment->update([
                    'gateway_order_id' => $orderId,
                    'gateway_provider' => $this->getProvider(),
                    'gateway_type' => $this->getType(),
                ]);

                $this->log('Payment initialized', [
                    'payment_id' => $payment->id,
                    'order_id' => $orderId,
                ]);

                return PaymentResult::success(
                    redirectUrl: $response['checkout_url'],
                    orderId: $orderId,
                    rawResponse: $response->json(),
                );
            }

            return PaymentResult::failure(
                $response['error'] ?? 'Payment initialization failed',
                null,
                $response->json()
            );
        } catch (\Exception $e) {
            $this->logError('Payment initialization error', ['error' => $e->getMessage()]);

            return PaymentResult::failure('Payment service unavailable');
        }
    }

    public function completePayment(Payment $payment, array $callbackData): PaymentResult
    {
        $transactionId = $callbackData['transaction_id'] ?? null;
        $status = $callbackData['status'] ?? null;

        if ($status === 'completed' && $transactionId) {
            $payment->markAsCompleted($transactionId, $callbackData);

            $this->log('Payment completed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return PaymentResult::success(transactionId: $transactionId);
        }

        $payment->markAsFailed(
            $callbackData['error'] ?? 'Payment failed',
            null,
            $callbackData
        );

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
                $refundId = $response['refund_id'] ?? null;
                $payment->markAsRefunded();

                if ($payment->ledger_entry_id) {
                    $providerRefundAmount = $payment->provider_amount * ($refundAmount / $payment->amount);
                    $this->ledgerService->debitForRefund($payment, $providerRefundAmount);
                }

                return RefundResult::success($refundId, $refundAmount, $response->json());
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
            $payment->markAsCompleted($transactionId, $payload);

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

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $this->secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public function recordToLedger(Payment $payment): LedgerEntry
    {
        return $this->ledgerService->creditProvider($payment);
    }
}
