<?php

namespace App\Domains\Payment\Controllers;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Payment\Services\PowerTranzGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private PowerTranzGateway $gateway
    ) {}

    /**
     * Handle PowerTranz webhook notifications.
     */
    public function handlePowerTranz(Request $request): JsonResponse
    {
        Log::info('PowerTranz webhook received', [
            'payload' => $request->all(),
        ]);

        $result = $this->gateway->handleWebhook($request->all());

        if ($result['success'] && isset($result['payment'])) {
            $payment = $result['payment'];

            // Auto-confirm booking on successful payment
            if ($payment->isCompleted() && $payment->booking->status === BookingStatus::PENDING) {
                $payment->booking->update([
                    'status' => BookingStatus::CONFIRMED,
                    'confirmed_at' => now(),
                ]);
            }

            return response()->json(['status' => 'success']);
        }

        return response()->json([
            'status' => 'failed',
            'message' => $result['error'] ?? 'Webhook processing failed',
        ], 400);
    }
}
