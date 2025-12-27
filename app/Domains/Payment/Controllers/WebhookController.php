<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Payment\Contracts\EscrowGatewayInterface;
use App\Domains\Payment\Contracts\PaymentGatewayInterface;
use App\Domains\Payment\Enums\GatewayProvider;
use App\Domains\Payment\Enums\GatewayType;
use App\Domains\Payment\Gateways\DirectSplit\FygaroDirectSplitGateway;
use App\Domains\Payment\Gateways\DirectSplit\WiPayDirectSplitGateway;
use App\Domains\Payment\Gateways\Escrow\FygaroEscrowGateway;
use App\Domains\Payment\Gateways\Escrow\PowerTranzEscrowGateway;
use App\Domains\Payment\Gateways\Escrow\WiPayEscrowGateway;
use App\Domains\Payment\Services\LedgerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private LedgerService $ledgerService
    ) {}

    /**
     * Handle webhook from a specific gateway.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        $gatewayProvider = GatewayProvider::tryFrom($gateway);

        if (! $gatewayProvider) {
            Log::warning('Unknown gateway webhook', ['gateway' => $gateway]);

            return response()->json(['error' => 'Unknown gateway'], 400);
        }

        Log::info("{$gateway} webhook received", [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        $gatewayInstance = $this->resolveGatewayForWebhook($gatewayProvider, $request);

        if (! $gatewayInstance) {
            return response()->json(['error' => 'Gateway not configured'], 400);
        }

        // Verify webhook signature
        if (! $gatewayInstance->verifyWebhookSignature($request)) {
            Log::warning("{$gateway} webhook signature verification failed");

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Process the webhook
        $result = $gatewayInstance->handleWebhook($request);

        if ($result->success) {
            // Handle post-payment processing for escrow gateways
            if ($gatewayInstance instanceof EscrowGatewayInterface && $result->paymentId) {
                $this->handleEscrowPaymentSuccess($gatewayInstance, $result->paymentId);
            }

            return response()->json(['status' => 'success']);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $result->error ?? 'Webhook processing failed',
        ], 400);
    }

    /**
     * Handle PowerTranz webhook notifications.
     */
    public function handlePowerTranz(Request $request): JsonResponse
    {
        return $this->handle($request, GatewayProvider::POWERTRANZ->value);
    }

    /**
     * Handle WiPay webhook notifications.
     */
    public function handleWiPay(Request $request): JsonResponse
    {
        return $this->handle($request, GatewayProvider::WIPAY->value);
    }

    /**
     * Handle Fygaro webhook notifications.
     */
    public function handleFygaro(Request $request): JsonResponse
    {
        return $this->handle($request, GatewayProvider::FYGARO->value);
    }

    /**
     * Resolve the gateway instance for webhook handling.
     */
    protected function resolveGatewayForWebhook(GatewayProvider $provider, Request $request): ?PaymentGatewayInterface
    {
        // Determine if this is a split or escrow payment from the payload
        $isSplit = $this->isSplitPaymentWebhook($request);

        return match ($provider) {
            GatewayProvider::POWERTRANZ => app(PowerTranzEscrowGateway::class),
            GatewayProvider::WIPAY => $isSplit
                ? app(WiPayDirectSplitGateway::class)
                : app(WiPayEscrowGateway::class),
            GatewayProvider::FYGARO => $isSplit
                ? app(FygaroDirectSplitGateway::class)
                : app(FygaroEscrowGateway::class),
        };
    }

    /**
     * Determine if the webhook is for a split payment.
     */
    protected function isSplitPaymentWebhook(Request $request): bool
    {
        // Check for split payment indicators in the payload
        $payload = $request->all();

        // WiPay split indicator
        if (isset($payload['split_payment']) && $payload['split_payment'] === true) {
            return true;
        }

        // Fygaro split indicator
        if (isset($payload['splits']) && is_array($payload['splits'])) {
            return true;
        }

        // Check the payment record for gateway type
        $orderId = $payload['order_id'] ?? $payload['OrderIdentifier'] ?? null;
        if ($orderId) {
            $payment = \App\Domains\Payment\Models\Payment::where('gateway_order_id', $orderId)
                ->orWhere('uuid', $orderId)
                ->first();

            if ($payment && $payment->gateway_type === GatewayType::SPLIT->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle post-payment processing for escrow payments.
     */
    protected function handleEscrowPaymentSuccess(EscrowGatewayInterface $gateway, int $paymentId): void
    {
        $payment = \App\Domains\Payment\Models\Payment::with('booking')->find($paymentId);

        if (! $payment || $payment->ledger_entry_id) {
            return;
        }

        // Record to ledger
        $ledgerEntry = $gateway->recordToLedger($payment);
        $payment->update(['ledger_entry_id' => $ledgerEntry->id]);

        // Auto-confirm booking if applicable
        $booking = $payment->booking;
        if ($booking && $booking->status === BookingStatus::PENDING) {
            $settings = $booking->service->getEffectiveBookingSettings();

            if (! $settings['requires_approval']) {
                if (! $booking->requiresDeposit() || $booking->isDepositPaid()) {
                    $booking->update([
                        'status' => BookingStatus::CONFIRMED,
                        'confirmed_at' => now(),
                    ]);
                }
            }
        }
    }
}
