<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use App\Services\CryptoPaymentService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function nowpayments(Request $request)
    {
        $payload = $request->all();
        $signature = $request->header('x-nowpayments-sig', '');

        $webhookLog = WebhookLog::create([
            'crypto_gateway_id' => 2,
            'event_type' => $payload['payment_status'] ?? 'unknown',
            'gateway_webhook_id' => $payload['payment_id'] ?? null,
            'payload' => $payload,
            'signature' => $signature,
        ]);

        try {
            $paymentService = app(CryptoPaymentService::class);

            if ($paymentService->handleWebhook($payload, $signature)) {
                $webhookLog->markAsProcessed();
                return response()->json(['success' => true]);
            }

            $webhookLog->markAsFailed('Signature verification failed');
            return response()->json(['error' => 'Verification failed'], 401);
        } catch (\Exception $e) {
            $webhookLog->markAsFailed($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
