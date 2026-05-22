<?php

namespace App\Services\Crypto\Gateways;

use App\Models\CryptoGateway;
use App\Models\CryptoTransaction;
use App\Services\Crypto\Contracts\CryptoGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CoinbaseCommerceGateway implements CryptoGatewayInterface
{
    protected CryptoGateway $gateway;
    protected string $baseUrl = 'https://api.commerce.coinbase.com';

    public function __construct(CryptoGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function createPaymentRequest(array $data): array
    {
        $payload = [
            'name' => $data['description'] ?? 'Payment',
            'description' => $data['description'] ?? '',
            'local_price' => [
                'amount' => (string)$data['amount'],
                'currency' => $data['currency'] ?? 'USD',
            ],
            'pricing_type' => 'fixed_price',
            'metadata' => [
                'order_id' => $data['order_id'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
            ],
        ];

        $response = Http::withHeaders([
            'X-CC-Api-Key' => $this->gateway->getApiKey(),
            'X-CC-Version' => '2018-03-22',
        ])->post("{$this->baseUrl}/charges", $payload);

        if (!$response->successful()) {
            throw new \Exception('Failed to create Coinbase Commerce charge: ' . $response->body());
        }

        $charge = $response->json('data');

        return [
            'gateway_transaction_id' => $charge['id'],
            'payment_address' => $charge['address'],
            'payment_url' => $charge['hosted_url'],
            'crypto_amount' => $charge['pricing']['crypto']['amount'] ?? null,
            'cryptocurrency' => $charge['pricing']['crypto']['currency'] ?? null,
            'exchange_rate' => $charge['pricing']['crypto']['amount'] ? 
                $data['amount'] / $charge['pricing']['crypto']['amount'] : null,
            'expires_at' => $charge['expires_at'],
            'gateway_response' => $charge,
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        $response = Http::withHeaders([
            'X-CC-Api-Key' => $this->gateway->getApiKey(),
            'X-CC-Version' => '2018-03-22',
        ])->get("{$this->baseUrl}/charges/{$transactionId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to get Coinbase Commerce charge status');
        }

        $charge = $response->json('data');

        return [
            'status' => $this->mapStatus($charge['timeline'][0]['status'] ?? 'new'),
            'confirmations' => $charge['confirmations'] ?? 0,
            'amount_received' => $charge['payments'][0]['value']['crypto']['amount'] ?? 0,
            'transaction_hash' => $charge['payments'][0]['transaction_id'] ?? null,
            'gateway_response' => $charge,
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
        $event = $payload['event']['type'] ?? null;
        $chargeId = $payload['event']['data']['id'] ?? null;

        if (!$chargeId) {
            return;
        }

        $transaction = CryptoTransaction::where('gateway_transaction_id', $chargeId)->first();
        if (!$transaction) {
            return;
        }

        match ($event) {
            'charge:confirmed' => $this->handleChargeConfirmed($transaction, $payload),
            'charge:failed' => $this->handleChargeFailed($transaction, $payload),
            'charge:delayed' => $this->handleChargeDelayed($transaction, $payload),
            default => null,
        };
    }

    public function getSupportedCurrencies(): array
    {
        return ['BTC', 'ETH', 'USDT', 'USDC', 'DAI', 'DOGE', 'LTC'];
    }

    public function validateConfiguration(): bool
    {
        return !empty($this->gateway->getApiKey());
    }

    public function getExchangeRate(string $cryptocurrency, string $fiatCurrency): ?float
    {
        $response = Http::withHeaders([
            'X-CC-Api-Key' => $this->gateway->getApiKey(),
            'X-CC-Version' => '2018-03-22',
        ])->get("{$this->baseUrl}/exchange-rates", [
            'crypto' => $cryptocurrency,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return (float)($response->json("data.rates.{$fiatCurrency}") ?? null);
    }

    protected function mapStatus(string $coinbaseStatus): string
    {
        return match ($coinbaseStatus) {
            'new' => 'pending',
            'pending' => 'waiting_confirmation',
            'confirmed' => 'confirmed',
            'failed' => 'failed',
            'expired' => 'expired',
            default => 'pending',
        };
    }

    protected function handleChargeConfirmed(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'gateway_response' => $payload['event']['data'],
        ]);

        if ($transaction->order) {
            $transaction->order->markAsPaid();
        }
    }

    protected function handleChargeFailed(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'failed',
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'gateway_response' => $payload['event']['data'],
        ]);
    }

    protected function handleChargeDelayed(CryptoTransaction $transaction, array $payload): void
    {
        $transaction->update([
            'status' => 'waiting_confirmation',
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
            'gateway_response' => $payload['event']['data'],
        ]);
    }
}
