# Crypto Payment Gateway - Code Examples

## Creating Orders and Payments

### Example 1: Create an Order

```php
use App\Models\Order;

$order = Order::create([
    'order_number' => 'ORD-' . time(),
    'user_id' => auth()->id(),
    'total_amount' => 100.00,
    'currency' => 'USD',
    'status' => 'pending',
    'description' => 'Product purchase',
    'items' => [
        ['name' => 'Product 1', 'quantity' => 1, 'price' => 100.00]
    ],
]);
```

### Example 2: Initiate Crypto Payment

```php
use App\Models\CryptoGateway;
use App\Services\Crypto\CryptoPaymentService;

$order = Order::find(1);
$gateway = CryptoGateway::where('slug', 'coinbase-commerce')->first();
$paymentService = app(CryptoPaymentService::class);

$transaction = $paymentService->createPayment(
    $order,
    $gateway,
    'BTC'
);

// Returns CryptoTransaction with:
// - payment_address
// - crypto_amount
// - expires_at
// - gateway_response
```

### Example 3: Check Payment Status

```php
$transaction = CryptoTransaction::find(1);
$paymentService = app(CryptoPaymentService::class);

$result = $paymentService->verifyPayment($transaction);

// Returns:
// [
//     'success' => true,
//     'status' => 'confirmed',
//     'confirmations' => 3,
//     'amount_received' => 0.001,
// ]
```

## Frontend Integration

### Example 4: Checkout Page (Blade)

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Checkout</h2>
    
    <div class="order-summary">
        <p>Order #: {{ $order->order_number }}</p>
        <p>Amount: {{ $order->currency }} {{ $order->total_amount }}</p>
    </div>

    <form id="paymentForm">
        @csrf
        <select name="gateway_id" id="gateway" required>
            <option value="">Select Payment Gateway</option>
            @foreach($gateways as $gateway)
                <option value="{{ $gateway['id'] }}">{{ $gateway['name'] }}</option>
            @endforeach
        </select>

        <select name="cryptocurrency" id="crypto" required>
            <option value="">Select Cryptocurrency</option>
        </select>

        <button type="button" onclick="createPayment()">Proceed to Payment</button>
    </form>
</div>

<script>
function createPayment() {
    const gatewayId = document.getElementById('gateway').value;
    const cryptocurrency = document.getElementById('crypto').value;

    fetch('{{ route("payments.create", $order) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            gateway_id: gatewayId,
            cryptocurrency: cryptocurrency
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect_url;
        }
    });
}
</script>
@endsection
```

### Example 5: Real-time Status Updates (JavaScript)

```javascript
// Poll for payment status every 10 seconds
const statusInterval = setInterval(() => {
    fetch(`/payments/check-status/{{ $transaction->id }}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateUI(data);
            
            if (data.status === 'confirmed') {
                clearInterval(statusInterval);
                window.location.href = '{{ route("payments.success") }}';
            }
        }
    });
}, 10000);

function updateUI(data) {
    document.getElementById('status').textContent = data.status;
    document.getElementById('confirmations').textContent = data.confirmations;
    document.getElementById('amount-received').textContent = data.amount_received;
}
```

## Admin Panel Usage

### Example 6: List All Gateways

```php
use App\Models\CryptoGateway;

$gateways = CryptoGateway::all();

foreach ($gateways as $gateway) {
    echo $gateway->name;
    echo $gateway->is_active ? 'Active' : 'Inactive';
    echo count($gateway->supported_currencies) . ' currencies';
}
```

### Example 7: View Gateway Transactions

```php
$gateway = CryptoGateway::find(1);
$transactions = $gateway->transactions()
    ->where('status', 'confirmed')
    ->latest()
    ->paginate(20);

foreach ($transactions as $transaction) {
    echo $transaction->transaction_reference;
    echo $transaction->crypto_amount . ' ' . $transaction->cryptocurrency;
    echo $transaction->fiat_amount . ' ' . $transaction->fiat_currency;
}
```

### Example 8: View Webhook Logs

```php
$gateway = CryptoGateway::find(1);
$logs = $gateway->webhookLogs()
    ->where('status', 'failed')
    ->latest()
    ->get();

foreach ($logs as $log) {
    echo $log->event_type;
    echo $log->error_message;
    echo json_encode($log->payload);
}
```

## Service Usage

### Example 9: Get Available Gateways

```php
use App\Services\Crypto\CryptoPaymentService;

$paymentService = app(CryptoPaymentService::class);
$gateways = $paymentService->getAvailableGateways();

foreach ($gateways as $gateway) {
    echo $gateway['name'];
    echo $gateway['slug'];
}
```

### Example 10: Get Supported Cryptocurrencies

```php
$gateway = CryptoGateway::find(1);
$paymentService = app(CryptoPaymentService::class);

$cryptos = $paymentService->getSupportedCryptocurrencies($gateway);
// Returns: ['BTC', 'ETH', 'USDT', ...]
```

### Example 11: Get Exchange Rate

```php
$gateway = CryptoGateway::find(1);
$paymentService = app(CryptoPaymentService::class);

$rate = $paymentService->getExchangeRate($gateway, 'BTC', 'USD');
// Returns: 42735.04
```

## Payment Logging

### Example 12: Log Payment Activity

```php
use App\Models\PaymentLog;

$transaction = CryptoTransaction::find(1);

PaymentLog::log(
    $transaction,
    'payment_confirmed',
    'success',
    'Payment confirmed with 3 confirmations',
    [
        'confirmations' => 3,
        'amount' => 0.001,
        'hash' => 'abc123...'
    ]
);
```

### Example 13: View Payment Logs

```php
$transaction = CryptoTransaction::find(1);
$logs = $transaction->paymentLogs()->latest()->get();

foreach ($logs as $log) {
    echo $log->action;
    echo $log->status;
    echo $log->message;
    echo json_encode($log->data);
}
```

## Webhook Handling

### Example 14: Handle Webhook Manually

```php
use App\Models\CryptoGateway;
use App\Services\Crypto\CryptoPaymentService;

$gateway = CryptoGateway::where('slug', 'coinbase-commerce')->first();
$paymentService = app(CryptoPaymentService::class);

$payload = request()->all();
$signature = request()->header('X-CC-Webhook-Signature');

if ($paymentService->handleWebhook($gateway, $payload, $signature)) {
    return response()->json(['success' => true]);
} else {
    return response()->json(['error' => 'Invalid signature'], 401);
}
```

### Example 15: Process Webhook Payload

```php
use App\Models\WebhookLog;

$gateway = CryptoGateway::find(1);
$payload = request()->all();
$signature = request()->header('X-CC-Webhook-Signature');

$log = WebhookLog::create([
    'crypto_gateway_id' => $gateway->id,
    'event_type' => $payload['event']['type'],
    'payload' => $payload,
    'signature' => $signature,
]);

try {
    $paymentService->handleWebhook($gateway, $payload, $signature);
    $log->markAsProcessed();
} catch (Exception $e) {
    $log->markAsFailed($e->getMessage());
}
```

## Database Queries

### Example 16: Find Pending Payments

```php
use App\Models\CryptoTransaction;

$pending = CryptoTransaction::pending()
    ->where('expires_at', '>', now())
    ->get();

foreach ($pending as $transaction) {
    echo $transaction->transaction_reference;
    echo $transaction->status;
}
```

### Example 17: Find Confirmed Payments

```php
$confirmed = CryptoTransaction::confirmed()
    ->where('created_at', '>', now()->subDay())
    ->get();

$totalAmount = $confirmed->sum('fiat_amount');
echo "Total confirmed payments: $" . $totalAmount;
```

### Example 18: Find Expired Payments

```php
$expired = CryptoTransaction::expired()->get();

foreach ($expired as $transaction) {
    $transaction->update(['status' => 'expired']);
}
```

## Testing Examples

### Example 19: Test Payment Creation

```php
use Tests\TestCase;
use App\Models\Order;
use App\Models\CryptoGateway;
use App\Services\Crypto\CryptoPaymentService;

class PaymentTest extends TestCase
{
    public function test_can_create_payment()
    {
        $order = Order::factory()->create();
        $gateway = CryptoGateway::first();
        $service = app(CryptoPaymentService::class);

        $transaction = $service->createPayment($order, $gateway, 'BTC');

        $this->assertNotNull($transaction->id);
        $this->assertEquals('pending', $transaction->status);
        $this->assertEquals('BTC', $transaction->cryptocurrency);
    }
}
```

### Example 20: Test Webhook Processing

```php
public function test_can_process_webhook()
{
    $gateway = CryptoGateway::where('slug', 'coinbase-commerce')->first();
    $transaction = CryptoTransaction::factory()
        ->for($gateway)
        ->create();

    $payload = [
        'event' => [
            'type' => 'charge:confirmed',
            'data' => [
                'id' => $transaction->gateway_transaction_id,
                'timeline' => [['status' => 'confirmed']],
                'payments' => [[
                    'value' => ['crypto' => ['amount' => '0.001']],
                    'transaction_id' => 'hash123'
                ]]
            ]
        ]
    ];

    $service = app(CryptoPaymentService::class);
    $result = $service->handleWebhook($gateway, $payload, 'signature');

    $this->assertTrue($result);
    $transaction->refresh();
    $this->assertEquals('confirmed', $transaction->status);
}
```

## Configuration Examples

### Example 21: Add Custom Gateway

```php
// In a service provider or migration

use App\Models\CryptoGateway;

CryptoGateway::create([
    'name' => 'Custom Gateway',
    'slug' => 'custom-gateway',
    'api_endpoint' => 'https://api.custom.com',
    'supported_currencies' => ['BTC', 'ETH'],
    'is_active' => true,
    'config' => [
        'api_key' => env('CUSTOM_GATEWAY_API_KEY'),
        'webhook_secret' => env('CUSTOM_GATEWAY_WEBHOOK_SECRET'),
    ],
]);
```

### Example 22: Update Gateway Configuration

```php
$gateway = CryptoGateway::find(1);

$gateway->update([
    'is_active' => true,
    'transaction_fee_percentage' => 1.5,
    'config' => [
        'api_key' => 'new_key',
        'webhook_secret' => 'new_secret',
    ],
]);
```

## Advanced Examples

### Example 23: Batch Process Payments

```php
use App\Jobs\SyncCryptoPaymentStatus;

$pendingTransactions = CryptoTransaction::pending()
    ->where('expires_at', '>', now())
    ->chunk(100, function ($transactions) {
        foreach ($transactions as $transaction) {
            dispatch(new SyncCryptoPaymentStatus($transaction));
        }
    });
```

### Example 24: Generate Payment Report

```php
$report = [
    'total_payments' => CryptoTransaction::count(),
    'confirmed_payments' => CryptoTransaction::confirmed()->count(),
    'pending_payments' => CryptoTransaction::pending()->count(),
    'failed_payments' => CryptoTransaction::where('status', 'failed')->count(),
    'total_amount' => CryptoTransaction::confirmed()->sum('fiat_amount'),
    'by_gateway' => CryptoGateway::withCount('transactions')->get(),
    'by_cryptocurrency' => CryptoTransaction::groupBy('cryptocurrency')
        ->selectRaw('cryptocurrency, count(*) as count, sum(fiat_amount) as total')
        ->get(),
];

return response()->json($report);
```

### Example 25: Monitor Payment Health

```php
$health = [
    'webhook_success_rate' => WebhookLog::where('status', 'processed')->count() / 
                              WebhookLog::count() * 100,
    'payment_success_rate' => CryptoTransaction::confirmed()->count() / 
                              CryptoTransaction::count() * 100,
    'average_confirmation_time' => CryptoTransaction::confirmed()
        ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, confirmed_at)) as avg')
        ->first()
        ->avg,
    'failed_webhooks' => WebhookLog::where('status', 'failed')->count(),
    'expired_payments' => CryptoTransaction::where('status', 'expired')->count(),
];

return response()->json($health);
```

## Summary

These examples cover:
- ✅ Creating orders and payments
- ✅ Checking payment status
- ✅ Frontend integration
- ✅ Admin panel usage
- ✅ Service layer usage
- ✅ Payment logging
- ✅ Webhook handling
- ✅ Database queries
- ✅ Testing
- ✅ Configuration
- ✅ Advanced operations

Use these examples as a reference for implementing crypto payments in your application!
