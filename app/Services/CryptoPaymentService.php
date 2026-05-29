<?php

namespace App\Services;

use App\Models\CryptoTransaction;
use App\Models\Order;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CryptoPaymentService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('crypto.nowpayments_api_key');
        $this->baseUrl = config('crypto.sandbox_mode')
            ? 'https://api-sandbox.nowpayments.io/v1'
            : 'https://api.nowpayments.io/v1';
    }

    protected array $popularCurrencies = ['BTC', 'ETH', 'USDT', 'USDC', 'BNB', 'LTC', 'DOGE', 'XRP', 'MATIC', 'TRX'];

    public function getSupportedCurrencies(): array
    {
        if (config('crypto.sandbox_mode')) {
            return $this->popularCurrencies;
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/currencies");

            if ($response->successful()) {
                $data = $response->json();
                $allCurrencies = array_map('strtoupper', $data['currencies'] ?? []);
                // Only return popular currencies that are actually supported
                $supported = array_values(array_intersect($this->popularCurrencies, $allCurrencies));
                if (!empty($supported)) {
                    return $supported;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch NOWPayments currencies: ' . $e->getMessage());
        }

        // Fallback: return popular currencies if API fails or returns empty
        return $this->popularCurrencies;
    }

    public function createPayment(Order $order, string $cryptocurrency): CryptoTransaction
    {
        $transactionReference = $this->generateTransactionReference();

        // Sandbox mode: simulate payment
        if (config('crypto.sandbox_mode')) {
            return $this->createSandboxPayment($order, $cryptocurrency, $transactionReference);
        }

        // Check minimum amount before calling API
        $minAmount = $this->getMinimumAmount($cryptocurrency);
        if ($order->total_amount < $minAmount) {
            throw new \Exception("Minimum payment amount is \${$minAmount} USD for {$cryptocurrency}. Your order is \${$order->total_amount}.");
        }

        // Real NOWPayments API call - use hosted checkout (no payout wallet required)
        $response = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->post("{$this->baseUrl}/invoice", [
                'price_amount' => (float) $order->total_amount,
                'price_currency' => $order->currency ?? 'USD',
                'order_id' => (string) $order->id,
                'order_description' => "Order #{$order->order_number}",
                'ipn_callback_url' => url('/webhooks/nowpayments'),
                'success_url' => url('/payments/success?transaction_id=' . $transactionReference),
                'cancel_url' => url('/payments/cancel'),
                'is_fee_paid_by_user' => false,
            ]);

        if (!$response->successful()) {
            throw new \Exception('NOWPayments API error: ' . $response->body());
        }

        $invoice = $response->json();
        $invoiceId = $invoice['id'];
        $invoiceUrl = $invoice['invoice_url'] ?? null;

        // Create payment for specific crypto from the invoice
        $paymentResponse = Http::withHeaders(['x-api-key' => $this->apiKey])
            ->post("{$this->baseUrl}/invoice-payments", [
                'iid' => $invoiceId,
                'pay_currency' => $cryptocurrency,
            ]);

        if (!$paymentResponse->successful()) {
            // If invoice-payments fails, redirect to hosted checkout page instead
            if ($invoiceUrl) {
                $transaction = CryptoTransaction::create([
                    'crypto_gateway_id' => 1,
                    'order_id' => $order->id,
                    'transaction_reference' => $transactionReference,
                    'gateway_transaction_id' => 'INV-' . $invoiceId,
                    'payment_address' => '',
                    'cryptocurrency' => $cryptocurrency,
                    'crypto_amount' => 0,
                    'fiat_amount' => $order->total_amount,
                    'fiat_currency' => $order->currency,
                    'exchange_rate' => 0,
                    'status' => 'pending',
                    'required_confirmations' => 1,
                    'expires_at' => now()->addMinutes(30),
                    'gateway_response' => $invoice,
                    'metadata' => ['invoice_url' => $invoiceUrl, 'invoice_id' => $invoiceId],
                ]);

                PaymentLog::log($transaction, 'create_payment', 'success', 'Invoice created, redirecting to hosted checkout');

                return $transaction;
            }
            throw new \Exception('NOWPayments payment error: ' . $paymentResponse->body());
        }

        $payment = $paymentResponse->json();

        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => 1,
            'order_id' => $order->id,
            'transaction_reference' => $transactionReference,
            'gateway_transaction_id' => $payment['payment_id'],
            'payment_address' => $payment['pay_address'],
            'cryptocurrency' => $payment['pay_currency'],
            'crypto_amount' => $payment['pay_amount'],
            'fiat_amount' => $order->total_amount,
            'fiat_currency' => $order->currency,
            'exchange_rate' => $payment['pay_amount'] ? $order->total_amount / $payment['pay_amount'] : null,
            'status' => 'pending',
            'required_confirmations' => 1,
            'expires_at' => now()->addMinutes(30),
            'gateway_response' => $payment,
            'metadata' => ['payment_url' => $payment['payment_url'] ?? null],
        ]);

        PaymentLog::log($transaction, 'create_payment', 'success', 'Payment created via NOWPayments');

        return $transaction;
    }

    protected function createSandboxPayment(Order $order, string $cryptocurrency, string $reference): CryptoTransaction
    {
        $rates = [
            'BTC' => 0.0000094, 'ETH' => 0.00028, 'USDT' => 1.0, 'USDC' => 1.0,
            'BNB' => 0.0015, 'LTC' => 0.011, 'DOGE' => 7.5, 'XRP' => 1.8,
        ];

        $addresses = [
            'BTC' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'ETH' => '0x71C7656EC7ab88b098defB751B7401B5fE6418bA',
            'USDT' => 'TN3W4H6rK2ce4vX9YnFQHwKENnHjoxb3m9',
            'USDC' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            'BNB' => 'bnb1grpf0955h0ykzq3ar5nmum7y6gdfl6lxfn2y8h',
            'LTC' => 'ltc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'DOGE' => 'DNYmxC6Y3KUQG3YPA2kwsG3tCKD2yklCpm',
            'XRP' => 'rHb9CJAWyB4rj91VRWn96DkucG2vzQ1p9D',
        ];

        $rate = $rates[$cryptocurrency] ?? 0.001;
        $cryptoAmount = round($order->total_amount * $rate, 8);

        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => 1,
            'order_id' => $order->id,
            'transaction_reference' => $reference,
            'gateway_transaction_id' => 'SANDBOX-' . strtoupper(Str::random(10)),
            'payment_address' => $addresses[$cryptocurrency] ?? 'sandbox_test_address',
            'cryptocurrency' => $cryptocurrency,
            'crypto_amount' => $cryptoAmount,
            'fiat_amount' => $order->total_amount,
            'fiat_currency' => $order->currency,
            'exchange_rate' => $rate,
            'status' => 'pending',
            'required_confirmations' => 1,
            'expires_at' => now()->addMinutes(30),
            'gateway_response' => ['sandbox' => true],
            'metadata' => ['sandbox' => true],
        ]);

        PaymentLog::log($transaction, 'create_payment', 'success', 'Sandbox payment created');

        return $transaction;
    }

    public function checkPaymentStatus(CryptoTransaction $transaction): array
    {
        if (config('crypto.sandbox_mode')) {
            return $this->getPaymentStatus($transaction);
        }

        try {
            $response = Http::withHeaders(['x-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/payment/{$transaction->gateway_transaction_id}");

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'Failed to check status'];
            }

            $data = $response->json();
            $status = $this->mapStatus($data['payment_status'] ?? 'pending');

            $transaction->update([
                'status' => $status,
                'confirmations' => $data['outcome_transactions'][0]['block_confirmations'] ?? 0,
                'amount_received' => $data['outcome_amount'] ?? null,
                'transaction_hash' => $data['outcome_transactions'][0]['tx_hash'] ?? null,
                'gateway_response' => $data,
            ]);

            if ($status === 'confirmed' && $transaction->order) {
                $transaction->order->markAsPaid();
            }

            return ['success' => true, 'status' => $status];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function handleWebhook(array $payload, string $signature): bool
    {
        if (!$this->verifyWebhookSignature($payload, $signature)) {
            \Log::warning('NOWPayments webhook signature verification failed');
            return false;
        }

        $paymentId = $payload['payment_id'] ?? null;
        if (!$paymentId) return false;

        $transaction = CryptoTransaction::where('gateway_transaction_id', $paymentId)->first();
        if (!$transaction) return false;

        $status = $this->mapStatus($payload['payment_status'] ?? 'pending');

        $transaction->update([
            'status' => $status,
            'confirmations' => $payload['outcome_transactions'][0]['block_confirmations'] ?? 0,
            'amount_received' => $payload['outcome_amount'] ?? $transaction->crypto_amount,
            'transaction_hash' => $payload['outcome_transactions'][0]['tx_hash'] ?? null,
            'gateway_response' => $payload,
            'webhook_status' => 'verified',
            'webhook_verified_at' => now(),
        ]);

        if ($status === 'confirmed' && $transaction->order) {
            $transaction->order->markAsPaid();
            PaymentLog::log($transaction, 'webhook_confirm', 'success', 'Payment confirmed via webhook');
        }

        return true;
    }

    protected function verifyWebhookSignature(array $payload, string $signature): bool
    {
        $secret = config('crypto.nowpayments_webhook_secret');
        if (!$secret) return false;

        $sortedKeys = array_keys($payload);
        sort($sortedKeys);

        $sortedPayload = [];
        foreach ($sortedKeys as $key) {
            $sortedPayload[$key] = $payload[$key];
        }

        $hmac = hash_hmac('sha512', json_encode($sortedPayload), $secret);
        return hash_equals($hmac, $signature);
    }

    protected function mapStatus(string $status): string
    {
        return match ($status) {
            'waiting', 'pending' => 'pending',
            'confirming' => 'waiting_confirmation',
            'confirmed', 'finished', 'sending' => 'confirmed',
            'failed' => 'failed',
            'expired' => 'expired',
            'refunded' => 'refunded',
            default => 'pending',
        };
    }

    public function getPaymentStatus(CryptoTransaction $transaction): array
    {
        return [
            'id' => $transaction->id,
            'status' => $transaction->status,
            'cryptocurrency' => $transaction->cryptocurrency,
            'crypto_amount' => $transaction->crypto_amount,
            'fiat_amount' => $transaction->fiat_amount,
            'payment_address' => $transaction->payment_address,
            'confirmations' => $transaction->confirmations,
            'expires_at' => $transaction->expires_at?->toIso8601String(),
        ];
    }

    protected function generateTransactionReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(12));
        } while (CryptoTransaction::where('transaction_reference', $reference)->exists());

        return $reference;
    }

    protected function getMinimumAmount(string $cryptocurrency): float
    {
        try {
            $response = Http::withHeaders(['x-api-key' => $this->apiKey])
                ->get("{$this->baseUrl}/min-amount", [
                    'currencyFrom' => 'USD',
                    'currencyTo' => $cryptocurrency,
                ]);

            if ($response->successful()) {
                return (float) ($response->json()['minAmountUSD'] ?? 1.0);
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch minimum amount: ' . $e->getMessage());
        }

        return 1.0;
    }
}
