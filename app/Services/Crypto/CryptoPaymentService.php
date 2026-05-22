<?php

namespace App\Services\Crypto;

use App\Models\CryptoGateway;
use App\Models\CryptoTransaction;
use App\Models\Order;
use App\Models\PaymentLog;
use App\Services\Crypto\Contracts\CryptoGatewayInterface;
use App\Services\Crypto\Gateways\CoinbaseCommerceGateway;
use App\Services\Crypto\Gateways\NOWPaymentsGateway;
use Illuminate\Support\Str;

class CryptoPaymentService
{
    public function getAvailableGateways(): array
    {
        return CryptoGateway::active()->ordered()->get()->toArray();
    }

    public function getGatewayInstance(CryptoGateway $gateway): CryptoGatewayInterface
    {
        return match ($gateway->slug) {
            'coinbase-commerce' => new CoinbaseCommerceGateway($gateway),
            'nowpayments' => new NOWPaymentsGateway($gateway),
            default => throw new \Exception("Unknown gateway: {$gateway->slug}"),
        };
    }

    public function createPayment(Order $order, CryptoGateway $gateway, string $cryptocurrency): CryptoTransaction
    {
        if (!$gateway->is_active) {
            throw new \Exception("Gateway {$gateway->name} is not active");
        }

        $gatewayInstance = $this->getGatewayInstance($gateway);

        if (!$gatewayInstance->validateConfiguration()) {
            throw new \Exception("Gateway {$gateway->name} is not properly configured");
        }

        $transactionReference = $this->generateTransactionReference();

        try {
            // Sandbox mode: simulate payment in local environment
            if (app()->environment('local') && config('crypto.sandbox_mode', true)) {
                $paymentData = $this->createSandboxPayment($order, $gateway, $cryptocurrency);
            } else {
                $paymentData = $gatewayInstance->createPaymentRequest([
                    'amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'cryptocurrency' => $cryptocurrency,
                    'order_id' => $order->id,
                    'description' => "Order #{$order->order_number}",
                    'customer_email' => $order->user?->email,
                    'reference_id' => $transactionReference,
                    'success_url' => route('payments.success'),
                    'cancel_url' => route('payments.cancel'),
                ]);
            }

            $transaction = CryptoTransaction::create([
                'crypto_gateway_id' => $gateway->id,
                'order_id' => $order->id,
                'transaction_reference' => $transactionReference,
                'gateway_transaction_id' => $paymentData['gateway_transaction_id'],
                'payment_address' => $paymentData['payment_address'],
                'cryptocurrency' => $cryptocurrency,
                'crypto_amount' => $paymentData['crypto_amount'],
                'fiat_amount' => $order->total_amount,
                'fiat_currency' => $order->currency,
                'exchange_rate' => $paymentData['exchange_rate'],
                'status' => 'pending',
                'required_confirmations' => $gateway->confirmation_required,
                'expires_at' => $paymentData['expires_at'],
                'gateway_response' => $paymentData['gateway_response'] ?? null,
                'metadata' => [
                    'payment_url' => $paymentData['payment_url'] ?? null,
                    'sandbox' => app()->environment('local'),
                ],
            ]);

            PaymentLog::log($transaction, 'create_payment', 'success', 
                app()->environment('local') ? 'Sandbox payment created' : 'Payment request created');

            return $transaction;
        } catch (\Exception $e) {
            PaymentLog::log(null, 'create_payment', 'failed', $e->getMessage());
            throw $e;
        }
    }

    protected function createSandboxPayment(Order $order, CryptoGateway $gateway, string $cryptocurrency): array
    {
        $exchangeRates = [
            'BTC' => 0.0000094,
            'ETH' => 0.00028,
            'USDT' => 1.0,
            'USDC' => 1.0,
            'BNB' => 0.0015,
            'LTC' => 0.011,
            'DOGE' => 7.5,
            'DAI' => 1.0,
            'XRP' => 1.8,
        ];

        $rate = $exchangeRates[$cryptocurrency] ?? 0.001;
        $cryptoAmount = round($order->total_amount * $rate, 8);

        $sandboxAddresses = [
            'BTC' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'ETH' => '0x71C7656EC7ab88b098defB751B7401B5fE6418bA',
            'USDT' => 'TN3W4H6rK2ce4vX9YnFQHwKENnHjoxb3m9',
            'USDC' => '0xA0b86991c6218b36c1d19D4a2e9Eb0cE3606eB48',
            'BNB' => 'bnb1grpf0955h0ykzq3ar5nmum7y6gdfl6lxfn2y8h',
            'LTC' => 'ltc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
            'DOGE' => 'DNYmxC6Y3KUQG3YPA2kwsG3tCKD2yklCpm',
            'DAI' => '0x6B175474E89094C44Da98b954EedeAC495271d0F',
            'XRP' => 'rHb9CJAWyB4rj91VRWn96DkucG2vzQ1p9D',
        ];

        return [
            'gateway_transaction_id' => 'SANDBOX-' . strtoupper(Str::random(10)),
            'payment_address' => $sandboxAddresses[$cryptocurrency] ?? 'sandbox_test_address',
            'payment_url' => null,
            'crypto_amount' => $cryptoAmount,
            'cryptocurrency' => $cryptocurrency,
            'exchange_rate' => $rate,
            'expires_at' => now()->addMinutes((int) config('crypto.payment_timeout', 30)),
            'gateway_response' => [
                'sandbox' => true,
                'message' => 'This is a sandbox payment for testing',
            ],
        ];
    }

    public function verifyPayment(CryptoTransaction $transaction): array
    {
        $gateway = $transaction->gateway;
        $gatewayInstance = $this->getGatewayInstance($gateway);

        try {
            $status = $gatewayInstance->getPaymentStatus($transaction->gateway_transaction_id);

            $transaction->update([
                'status' => $status['status'],
                'confirmations' => $status['confirmations'],
                'amount_received' => $status['amount_received'],
                'transaction_hash' => $status['transaction_hash'],
                'gateway_response' => $status['gateway_response'],
            ]);

            if ($transaction->hasConfirmed() && !$transaction->isConfirmed()) {
                $transaction->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);

                if ($transaction->order) {
                    $transaction->order->markAsPaid();
                }

                PaymentLog::log($transaction, 'verify_payment', 'success', 'Payment confirmed');
            }

            return [
                'success' => true,
                'status' => $transaction->status,
                'confirmations' => $transaction->confirmations,
                'amount_received' => $transaction->amount_received,
            ];
        } catch (\Exception $e) {
            PaymentLog::log($transaction, 'verify_payment', 'failed', $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function handleWebhook(CryptoGateway $gateway, array $payload, string $signature): bool
    {
        $gatewayInstance = $this->getGatewayInstance($gateway);

        if (!$gatewayInstance->verifyWebhook($payload, $signature)) {
            return false;
        }

        try {
            $gatewayInstance->handleWebhookPayload($payload);
            return true;
        } catch (\Exception $e) {
            \Log::error("Webhook handling failed for {$gateway->name}: {$e->getMessage()}");
            return false;
        }
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
            'required_confirmations' => $transaction->required_confirmations,
            'amount_received' => $transaction->amount_received,
            'transaction_hash' => $transaction->transaction_hash,
            'expires_at' => $transaction->expires_at,
            'confirmed_at' => $transaction->confirmed_at,
        ];
    }

    public function getSupportedCryptocurrencies(CryptoGateway $gateway): array
    {
        $gatewayInstance = $this->getGatewayInstance($gateway);
        return $gatewayInstance->getSupportedCurrencies();
    }

    public function getExchangeRate(CryptoGateway $gateway, string $cryptocurrency, string $fiatCurrency): ?float
    {
        try {
            $gatewayInstance = $this->getGatewayInstance($gateway);
            return $gatewayInstance->getExchangeRate($cryptocurrency, $fiatCurrency);
        } catch (\Exception $e) {
            \Log::error("Failed to get exchange rate: {$e->getMessage()}");
            return null;
        }
    }

    public function generateTransactionReference(): string
    {
        do {
            $reference = 'TXN-' . strtoupper(Str::random(12));
        } while (CryptoTransaction::where('transaction_reference', $reference)->exists());

        return $reference;
    }

    public function checkExpiredPayments(): void
    {
        CryptoTransaction::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }

    public function syncPaymentStatuses(): void
    {
        $pendingTransactions = CryptoTransaction::whereIn('status', ['pending', 'waiting_confirmation'])
            ->where('expires_at', '>', now())
            ->get();

        foreach ($pendingTransactions as $transaction) {
            $this->verifyPayment($transaction);
        }
    }
}
