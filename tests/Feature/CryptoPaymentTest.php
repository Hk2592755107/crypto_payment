<?php

namespace Tests\Feature;

use App\Models\CryptoGateway;
use App\Models\CryptoTransaction;
use App\Models\Order;
use App\Models\User;
use App\Services\Crypto\CryptoPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CryptoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected CryptoPaymentService $paymentService;
    protected User $user;
    protected CryptoGateway $gateway;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(CryptoPaymentService::class);
        $this->user = User::factory()->create();
        
        $this->gateway = CryptoGateway::create([
            'name' => 'Test Gateway',
            'slug' => 'test-gateway',
            'api_endpoint' => 'https://api.test.com',
            'supported_currencies' => ['BTC', 'ETH'],
            'is_active' => true,
            'config' => [
                'api_key' => 'test_key',
                'webhook_secret' => 'test_secret',
            ],
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-' . time(),
            'user_id' => $this->user->id,
            'total_amount' => 100.00,
            'currency' => 'USD',
            'status' => 'pending',
        ]);
    }

    public function test_can_generate_transaction_reference(): void
    {
        $ref1 = $this->paymentService->generateTransactionReference();
        $ref2 = $this->paymentService->generateTransactionReference();

        $this->assertNotEquals($ref1, $ref2);
        $this->assertStringStartsWith('TXN-', $ref1);
        $this->assertStringStartsWith('TXN-', $ref2);
    }

    public function test_can_get_available_gateways(): void
    {
        $gateways = $this->paymentService->getAvailableGateways();

        $this->assertIsArray($gateways);
        $this->assertCount(1, $gateways);
        $this->assertEquals('Test Gateway', $gateways[0]['name']);
    }

    public function test_can_get_supported_cryptocurrencies(): void
    {
        $cryptos = $this->paymentService->getSupportedCryptocurrencies($this->gateway);

        $this->assertIsArray($cryptos);
    }

    public function test_transaction_reference_is_unique(): void
    {
        $ref = $this->paymentService->generateTransactionReference();
        
        CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $ref,
            'gateway_transaction_id' => 'test_id',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
        ]);

        $newRef = $this->paymentService->generateTransactionReference();
        $this->assertNotEquals($ref, $newRef);
    }

    public function test_payment_status_structure(): void
    {
        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
            'confirmations' => 0,
            'required_confirmations' => 1,
        ]);

        $status = $this->paymentService->getPaymentStatus($transaction);

        $this->assertArrayHasKey('id', $status);
        $this->assertArrayHasKey('status', $status);
        $this->assertArrayHasKey('cryptocurrency', $status);
        $this->assertArrayHasKey('crypto_amount', $status);
        $this->assertArrayHasKey('fiat_amount', $status);
        $this->assertArrayHasKey('payment_address', $status);
        $this->assertArrayHasKey('confirmations', $status);
    }

    public function test_transaction_status_methods(): void
    {
        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
        ]);

        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isConfirmed());
        $this->assertFalse($transaction->isFailed());

        $transaction->update(['status' => 'confirmed']);
        $this->assertTrue($transaction->isConfirmed());
        $this->assertFalse($transaction->isPending());
    }

    public function test_order_payment_relationship(): void
    {
        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
        ]);

        $this->assertEquals($this->order->id, $transaction->order->id);
        $this->assertEquals($this->gateway->id, $transaction->gateway->id);
    }

    public function test_can_check_expired_payments(): void
    {
        $transaction = CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);

        $this->paymentService->checkExpiredPayments();

        $transaction->refresh();
        $this->assertEquals('expired', $transaction->status);
    }

    public function test_gateway_is_active_scope(): void
    {
        $inactiveGateway = CryptoGateway::create([
            'name' => 'Inactive Gateway',
            'slug' => 'inactive-gateway',
            'api_endpoint' => 'https://api.inactive.com',
            'supported_currencies' => ['BTC'],
            'is_active' => false,
            'config' => ['api_key' => 'test'],
        ]);

        $activeGateways = CryptoGateway::active()->get();

        $this->assertEquals(1, $activeGateways->count());
        $this->assertEquals('Test Gateway', $activeGateways->first()->name);
    }

    public function test_transaction_pending_scope(): void
    {
        CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id_1',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'pending',
        ]);

        CryptoTransaction::create([
            'crypto_gateway_id' => $this->gateway->id,
            'order_id' => $this->order->id,
            'transaction_reference' => $this->paymentService->generateTransactionReference(),
            'gateway_transaction_id' => 'test_id_2',
            'payment_address' => 'test_address',
            'cryptocurrency' => 'BTC',
            'crypto_amount' => 0.001,
            'fiat_amount' => 100.00,
            'fiat_currency' => 'USD',
            'exchange_rate' => 100000,
            'status' => 'confirmed',
        ]);

        $pending = CryptoTransaction::pending()->get();

        $this->assertEquals(1, $pending->count());
        $this->assertEquals('pending', $pending->first()->status);
    }
}
