<?php

namespace App\Services\Crypto\Gateways;

use App\Models\CryptoGateway;
use App\Models\CryptoTransaction;
use App\Services\Crypto\Contracts\CryptoGatewayInterface;
use Illuminate\Support\Facades\Http;

class NOWPaymentsGateway implements CryptoGatewayInterface
{
    protected CryptoGateway $gateway;
    protected string $baseUrl;

    public function __construct(CryptoGateway $gateway)
    {
        $this->gateway = $gateway;
        // Use sandbox API endpoint when sandbox mode is on
        $this->baseUrl = config('crypto.sandbox_mode') 
            ? config('crypto.gateways.nowpayments.sandbox_api_endpoint', 'https://api-sandbox.nowpayments.io/v1')
            : config('crypto.gateways.nowpayments.api_endpoint', 'https://api.nowpayments.io/v1');
    }

    public function createPaymentRequest(array $data): array
    {
        $payload = [
            'price_amount' => (float)$data['amount'],
            'price_currency' => $data['currency'] ?? 'USD',
            'pay_currency' => $data['cryptocurrency'] ?? 'BTC',
            'order_id' => $data['order_id'] ?? null,
            'order_description' => $data['description'] ?? null,
            'ipn_callback_url' => route('webhooks.nowpayments'),
            'success_url' => $data['success_url'] ?? null,
            'cancel_url' => $data['cancel_url'] ?? null,
        ];

        $response = Http::withHeaders([
            'x-api-key' => $this->gateway->getApiKey(),
        ])->post("{$this->baseUrl}/payment", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create NOWPayments invoice: ' . $response->body());
        }

        $payment = $response->json();

        return [
            'gateway_transaction_id' => $payment['payment_id'],
            'payment_address' => $payment['pay_address'],
            'payment_url' => $payment['payment_url'],
            'crypto_amount' => $payment['pay_amount'],
            'cryptocurrency' => $payment['pay_currency'],
            'exchange_rate' => $payment['pay_amount'] ? 
                $data['amount'] / $payment['pay_amount'] : null,
            'expires_at' => now()->addMinutes(30),
            'gateway_response' => $payment,
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->gateway->getApiKey(),
        ])->get("{$this->baseUrl}/payment/{$transactionId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to get NOWPayments status');
        }

        $payment = $response->json();

        return [
            'status' => $this->mapStatus($payment['payment_status']),
            'confirmations' => $payment['confirmations'] ?? 0,
            'amount_received' => $payment['pay_amount'] ?? 0,
            'transaction_hash' => $payment['tx_hash'] ?? null,
            'gateway_response' => $payment,
        ];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $secret = $this->gateway->getWebhookSecret();
        $computedSignature = hash_hmac('sha256', json_encode($payload), $secret);
        
        return hash_equals($computedSignature, $signature);
    }

    public function handleWebhookPayload(array $payload): void
    {
        $paymentId = $payload['payment_id'] ?? null;
        $status = $payload['payment_status'] ?? null;

        if (!$paymentId) {
            return;
        }

        $transaction = CryptoTransaction::where('gateway_transaction_id', $paymentId)->first();
        if (!$transaction) {
            return;
        }

        match ($status) {
            'finished' => $this->handlePaymentFinished($transaction, $payload),
            'failed' => $this->handlePaymentFailed($transaction, $payload),
            'expired' => $this->handlePaymentExpired($transaction, $payload),
            default => null,
        };
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'BNB', 'LTC', 'DOGE', 'XRP'];
    }

    public function validateConfiguration(): bool
    {
        return !empty($this->gateway->getApiKey());
    }

    public function getExchangeRate(string $cryptocurrency, string $fiatCurrency): ?float
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->gateway->getApiKey(),
        ])->get("{$this->baseUrl}/ticker", [
            'symbol' => strtoupper($cryptocurrency),
        ]);

        if (!$response->successful()) {
            return null;
        }

        $rates = $response->json();
        $currencyKey = strtoupper($fiatCurrency);

        return (float)($rates[$currencyKey] ?? null);
    }

    protected function mapStatus(string $nowStatus): string
    {
        return match ($nowStatus) {
            'waiting' => 'pending',
            'confirming' => 'waiting_confirmation',
            'confirmed' => 'confirmed',
            'sending' => 'waiting_confirmation',
            'finished' => 'confirmed',
            'failed' => 'failed',
            'expired' => 'expired',
            default => 'pending',
        };
    }

    protected function handlePaymentFinished(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'transaction_hash' => $payload['tx_hash'] ?? null,
            'amount_received' => $payload['pay_amount'] ?? $transaction->crypto_amount,
            'gateway_response' => $payload,
        ]);

        if ($transaction->order) {
            $transaction->order->markAsPaid();
        }
    }

    protected function handlePaymentFailed(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'failed',
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'gateway_response' => $payload,
        ]);
    }

    protected function handlePaymentExpired(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'expired',
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'gateway_response' => $payload,
        ]);
    }
}
