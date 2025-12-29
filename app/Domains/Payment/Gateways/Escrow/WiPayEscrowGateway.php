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

class WiPayEscrowGateway extends AbstractGateway implements EscrowGatewayInterface
{
    protected LedgerService $ledgerService;

    protected string $accountNumber;

    protected string $apiKey;

    protected string $countryCode;

    public function __construct(?LedgerService $ledgerService = null)
    {
        parent::__construct();
        $this->ledgerService = $ledgerService ?? app(LedgerService::class);
        $this->accountNumber = config('services.wipay.platform_account_id', '');
        $this->apiKey = config('services.wipay.api_key', '');
        $this->countryCode = config('services.wipay.country_code', 'JM');
    }

    public function getProvider(): string
    {
        return 'wipay';
    }

    public function getType(): string
    {
        return GatewayType::ESCROW->value;
    }

    protected function isTestMode(): bool
    {
        return config('services.wipay.test_mode', true);
    }

    protected function getBaseUrl(): string
    {
        return config('services.wipay.api_url');
    }

    /**
     * Get default headers for form requests (required by AbstractGateway).
     */
    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    /**
     * Get headers for JSON API requests (e.g., refunds).
     */
    protected function getJsonHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    public function isAvailable(): bool
    {
        return ! empty($this->accountNumber) && ! empty($this->apiKey);
    }

    public function getSupportedCurrencies(): array
    {
        return ['JMD', 'TTD', 'USD'];
    }

    public function initializePayment(
        Payment $payment,
        string $returnUrl,
        string $cancelUrl
    ): PaymentResult {
        $orderId = 'WP-' . strtoupper(Str::random(12));

        // Determine fee structure based on provider settings
        // 'customer_pay' = client pays fees, 'merchant_absorb' = provider pays fees
        $feeStructure = $payment->processing_fee_payer === 'client' ? 'customer_pay' : 'merchant_absorb';

        $requestData = [
            'account_number' => $this->testMode ? '1234567890' : $this->accountNumber,
            'country_code' => $this->countryCode,
            'currency' => $payment->currency ?? 'JMD',
            'environment' => $this->testMode ? 'sandbox' : 'live',
            'fee_structure' => $feeStructure,
            'method' => 'credit_card',
            'order_id' => $orderId,
            'origin' => 'zeen_connect',
            'response_url' => $returnUrl,
            'total' => number_format($payment->amount, 2, '.', ''),
            'avs' => 0,
            'data' => json_encode(['payment_uuid' => $payment->uuid]),
        ];

        $this->logHttpRequest('POST', $this->baseUrl, $requestData);
        $startTime = microtime(true);

        try {
            // Use Laravel's Http facade directly for reliable form encoding
            $response = Http::asForm()
                ->accept('application/json')
                ->post($this->baseUrl, $requestData);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logHttpResponse($response, 'POST', $this->baseUrl, $durationMs);

            $data = $response->json();

            if ($response->successful() && isset($data['url'])) {
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
                    redirectUrl: $data['url'],
                    orderId: $orderId,
                    rawResponse: $data,
                );
            }

            return PaymentResult::failure(
                $data['message'] ?? 'Payment initialization failed',
                null,
                $data
            );
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logHttpError('POST', $this->baseUrl, $e, $durationMs);

            return PaymentResult::failure('Payment service unavailable');
        }
    }

    public function completePayment(Payment $payment, array $callbackData): PaymentResult
    {
        $transactionId = $callbackData['transaction_id'] ?? null;
        $status = $callbackData['status'] ?? null;

        if ($status === 'success' && $transactionId) {
            $payment->markAsCompleted($transactionId, $callbackData);

            $this->log('Payment completed', [
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
            ]);

            return PaymentResult::success(transactionId: $transactionId);
        }

        $payment->markAsFailed(
            $callbackData['message'] ?? 'Payment failed',
            null,
            $callbackData
        );

        return PaymentResult::failure($callbackData['message'] ?? 'Payment failed');
    }

    public function refund(Payment $payment, ?float $amount = null): RefundResult
    {
        $refundAmount = $amount ?? $payment->amount;
        $refundUrl = rtrim($this->baseUrl, '/') . '/refund';

        $requestData = [
            'transaction_id' => $payment->gateway_transaction_id,
            'amount' => $this->formatAmount($refundAmount),
        ];

        $this->logHttpRequest('POST', $refundUrl, $requestData, $this->getJsonHeaders());
        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($this->getJsonHeaders())
                ->post($refundUrl, $requestData);

            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logHttpResponse($response, 'POST', $refundUrl, $durationMs);

            $data = $response->json();
            if ($response->successful() && ($data['status'] ?? '') === 'success') {
                $refundId = $data['refund_id'] ?? null;
                $payment->markAsRefunded();

                if ($payment->ledger_entry_id) {
                    $providerRefundAmount = $payment->provider_amount * ($refundAmount / $payment->amount);
                    $this->ledgerService->debitForRefund($payment, $providerRefundAmount);
                }

                return RefundResult::success($refundId, $refundAmount, $data);
            }

            return RefundResult::failure(
                $data['message'] ?? 'Refund failed',
                null,
                $data
            );
        } catch (\Exception $e) {
            $durationMs = (microtime(true) - $startTime) * 1000;
            $this->logHttpError('POST', $refundUrl, $e, $durationMs);

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
            $payment->markAsCompleted($transactionId, $payload);

            return WebhookResult::success($payment->id, $transactionId, 'completed', $payload);
        }

        $payment->markAsFailed($payload['message'] ?? 'Payment failed', null, $payload);

        return WebhookResult::failure($payload['message'] ?? 'Payment failed', $payload);
    }

    public function recordToLedger(Payment $payment): LedgerEntry
    {
        return $this->ledgerService->creditProvider($payment);
    }
}
