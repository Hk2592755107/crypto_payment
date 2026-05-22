# Crypto Payment Gateway Integration Guide

## Overview

This guide provides a complete implementation of a crypto payment gateway system for Laravel 12. The system supports multiple cryptocurrency payment providers and is designed to be scalable and secure.

## Architecture

### Service Layer Pattern
The implementation uses a service-oriented architecture with:
- **CryptoPaymentService**: Main service handling payment operations
- **CryptoGatewayInterface**: Contract for gateway implementations
- **Gateway Implementations**: Specific implementations for each provider (Coinbase Commerce, NOWPayments)

### Database Schema
- `crypto_gateways`: Gateway configurations
- `crypto_transactions`: Payment transactions
- `payment_logs`: Transaction activity logs
- `webhook_logs`: Webhook event logs
- `orders`: Customer orders
- `order_payments`: Order payment records

## Setup Instructions

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Run Migrations

```bash
php artisan migrate
```

This creates all necessary tables for the crypto payment system.

### 3. Configure Gateways

#### Option A: Using Admin Panel
1. Navigate to `/admin/crypto-gateways`
2. Click "Add Gateway"
3. Fill in the gateway details:
   - Name: e.g., "Coinbase Commerce"
   - Slug: e.g., "coinbase-commerce"
   - API Key: Get from your gateway dashboard
   - Webhook Secret: Get from your gateway dashboard
   - Select supported cryptocurrencies
   - Enable the gateway

#### Option B: Using Database Seeder
Create a seeder in `database/seeders/CryptoGatewaySeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\CryptoGateway;
use Illuminate\Database\Seeder;

class CryptoGatewaySeeder extends Seeder
{
    public function run(): void
    {
        CryptoGateway::create([
            'name' => 'Coinbase Commerce',
            'slug' => 'coinbase-commerce',
            'description' => 'Accept crypto payments via Coinbase Commerce',
            'api_endpoint' => 'https://api.commerce.coinbase.com',
            'webhook_endpoint' => env('APP_URL') . '/webhooks/coinbase-commerce',
            'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'DAI', 'DOGE', 'LTC'],
            'is_active' => true,
            'priority' => 1,
            'transaction_fee_percentage' => 1.0,
            'min_transaction_amount' => 0.01,
            'max_transaction_amount' => null,
            'confirmation_required' => 1,
            'config' => [
                'api_key' => env('COINBASE_COMMERCE_API_KEY'),
                'webhook_secret' => env('COINBASE_COMMERCE_WEBHOOK_SECRET'),
            ],
        ]);

        CryptoGateway::create([
            'name' => 'NOWPayments',
            'slug' => 'nowpayments',
            'description' => 'Accept crypto payments via NOWPayments',
            'api_endpoint' => 'https://api.nowpayments.io/v1',
            'webhook_endpoint' => env('APP_URL') . '/webhooks/nowpayments',
            'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'BNB', 'LTC', 'DOGE', 'XRP'],
            'is_active' => true,
            'priority' => 2,
            'transaction_fee_percentage' => 0.5,
            'min_transaction_amount' => 0.01,
            'max_transaction_amount' => null,
            'confirmation_required' => 1,
            'config' => [
                'api_key' => env('NOWPAYMENTS_API_KEY'),
                'webhook_secret' => env('NOWPAYMENTS_WEBHOOK_SECRET'),
            ],
        ]);
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=CryptoGatewaySeeder
```

### 4. Configure Environment Variables

Update your `.env` file:

```env
CRYPTO_PAYMENT_TIMEOUT=30
CRYPTO_CONFIRMATION_TIMEOUT=3600
CRYPTO_WEBHOOK_TIMEOUT=300
CRYPTO_MAX_RETRIES=3
CRYPTO_RETRY_DELAY=60

COINBASE_COMMERCE_API_KEY=your_api_key_here
COINBASE_COMMERCE_WEBHOOK_SECRET=your_webhook_secret_here

NOWPAYMENTS_API_KEY=your_api_key_here
NOWPAYMENTS_WEBHOOK_SECRET=your_webhook_secret_here
```

### 5. Set Up Webhooks

Configure webhook URLs in your payment gateway dashboard:

**Coinbase Commerce:**
- Webhook URL: `https://yourapp.com/webhooks/coinbase-commerce`
- Events: charge:confirmed, charge:failed, charge:delayed

**NOWPayments:**
- Webhook URL: `https://yourapp.com/webhooks/nowpayments`
- Events: payment status updates

### 6. Register Middleware

Update `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... existing middleware
    'admin' => \App\Http\Middleware\IsAdmin::class,
];
```

### 7. Add is_admin Column to Users

Create a migration:

```bash
php artisan make:migration add_is_admin_to_users_table
```

Update the migration:

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_admin')->default(false);
    });
}
```

Run the migration:
```bash
php artisan migrate
```

### 8. Start Queue Worker

For webhook processing and payment status updates:

```bash
php artisan queue:work
```

Or use the dev command:
```bash
composer run dev
```

### 9. Set Up Scheduler

The scheduler runs payment status checks and expiry checks. Ensure your server has a cron job:

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Usage

### Creating a Payment

```php
use App\Models\Order;
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

// Returns CryptoTransaction with payment details
```

### Checking Payment Status

```php
$result = $paymentService->verifyPayment($transaction);

// Returns:
// [
//     'success' => true,
//     'status' => 'confirmed',
//     'confirmations' => 3,
//     'amount_received' => 0.001,
// ]
```

### Handling Webhooks

Webhooks are automatically processed by the `WebhookController`. The system:
1. Validates webhook signature
2. Logs webhook event
3. Updates transaction status
4. Marks order as paid if confirmed

## Frontend Integration

### Checkout Flow

1. **Checkout Page** (`/payments/checkout/{order}`)
   - Display order summary
   - Select payment gateway
   - Select cryptocurrency

2. **Payment Confirmation** (`/payments/confirm/{transaction}`)
   - Display wallet address
   - Show QR code
   - Display payment amount
   - Real-time status updates via AJAX polling

3. **Success Page** (`/payments/success`)
   - Confirm payment received
   - Display transaction details
   - Show order status

### AJAX Polling

The payment confirmation page automatically polls for status updates every 10 seconds:

```javascript
fetch(`/payments/check-status/{{ $transaction->id }}`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
    }
})
.then(response => response.json())
.then(data => {
    // Update UI with payment status
});
```

## Payment Statuses

- **pending**: Waiting for payment to be sent
- **waiting_confirmation**: Payment received, waiting for blockchain confirmation
- **partially_paid**: Partial payment received
- **confirmed**: Payment fully confirmed
- **failed**: Payment failed
- **expired**: Payment request expired
- **refunded**: Payment refunded

## Security Features

### 1. Webhook Signature Verification
All webhooks are verified using HMAC-SHA256 signatures before processing.

### 2. CSRF Protection
All POST requests are protected with CSRF tokens.

### 3. Database Transactions
Payment operations use database transactions to ensure consistency.

### 4. Duplicate Prevention
Transaction references are unique to prevent duplicate payments.

### 5. Rate Limiting
Implement rate limiting on payment endpoints:

```php
Route::middleware('throttle:60,1')->group(function () {
    Route::post('payments/create/{order}', [PaymentController::class, 'createPayment']);
});
```

### 6. API Key Security
- Store API keys in environment variables
- Never commit `.env` to version control
- Rotate keys regularly
- Use separate keys for development and production

## Adding New Gateways

To add a new payment gateway:

### 1. Create Gateway Class

Create `app/Services/Crypto/Gateways/YourGateway.php`:

```php
<?php

namespace App\Services\Crypto\Gateways;

use App\Services\Crypto\Contracts\CryptoGatewayInterface;
use App\Models\CryptoGateway;

class YourGateway implements CryptoGatewayInterface
{
    protected CryptoGateway $gateway;

    public function __construct(CryptoGateway $gateway)
    {
        $this->gateway = $gateway;
    }

    public function createPaymentRequest(array $data): array
    {
        // Implementation
    }

    public function getPaymentStatus(string $transactionId): array
    {
        // Implementation
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        // Implementation
    }

    public function handleWebhookPayload(array $payload): void
    {
        // Implementation
    }

    public function getSupportedCurrencies(): array
    {
        // Implementation
    }

    public function validateConfiguration(): bool
    {
        // Implementation
    }

    public function getExchangeRate(string $cryptocurrency, string $fiatCurrency): ?float
    {
        // Implementation
    }
}
```

### 2. Register in CryptoPaymentService

Update `getGatewayInstance()` method:

```php
public function getGatewayInstance(CryptoGateway $gateway): CryptoGatewayInterface
{
    return match ($gateway->slug) {
        'coinbase-commerce' => new CoinbaseCommerceGateway($gateway),
        'nowpayments' => new NOWPaymentsGateway($gateway),
        'your-gateway' => new YourGateway($gateway),
        default => throw new \Exception("Unknown gateway: {$gateway->slug}"),
    };
}
```

### 3. Add Webhook Route

Update `routes/webhooks.php`:

```php
Route::post('/webhooks/your-gateway', [WebhookController::class, 'yourGateway'])->name('webhooks.your-gateway');
```

## Admin Panel

Access the admin panel at `/admin/crypto-gateways` (requires admin user).

### Features:
- View all configured gateways
- Add/edit/delete gateways
- View transactions per gateway
- View webhook logs
- Monitor payment statuses

## Troubleshooting

### Webhooks Not Processing

1. Check webhook logs: `/admin/crypto-gateways/{gateway}/webhook-logs`
2. Verify webhook secret in gateway configuration
3. Ensure queue worker is running: `php artisan queue:work`
4. Check Laravel logs: `storage/logs/laravel.log`

### Payment Status Not Updating

1. Ensure scheduler is running
2. Check payment logs in database
3. Verify API credentials
4. Check gateway API status

### QR Code Not Displaying

1. Ensure payment address is valid
2. Check QR code API availability
3. Verify HTTPS is enabled (some QR services require it)

## Testing

### Manual Testing

1. Create an order
2. Go to checkout page
3. Select gateway and cryptocurrency
4. Complete payment in test mode
5. Verify webhook processing
6. Check order status

### Automated Testing

Create tests in `tests/Feature/CryptoPaymentTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\CryptoGateway;
use App\Services\Crypto\CryptoPaymentService;
use Tests\TestCase;

class CryptoPaymentTest extends TestCase
{
    public function test_can_create_crypto_payment()
    {
        $order = Order::factory()->create();
        $gateway = CryptoGateway::first();
        $service = app(CryptoPaymentService::class);

        $transaction = $service->createPayment($order, $gateway, 'BTC');

        $this->assertNotNull($transaction->id);
        $this->assertEquals('pending', $transaction->status);
    }
}
```

## Performance Optimization

### 1. Database Indexing
Indexes are already created on:
- `transaction_reference`
- `gateway_transaction_id`
- `status`
- `created_at`

### 2. Caching
Cache gateway configurations:

```php
$gateways = Cache::remember('crypto_gateways', 3600, function () {
    return CryptoGateway::active()->get();
});
```

### 3. Queue Processing
Use queue for:
- Webhook processing
- Payment status verification
- Email notifications

## Monitoring

### Key Metrics to Monitor

1. **Payment Success Rate**: Confirmed / Total payments
2. **Average Confirmation Time**: Time from pending to confirmed
3. **Webhook Failures**: Failed webhook processing
4. **API Response Time**: Gateway API latency
5. **Transaction Volume**: Payments per hour/day

### Logging

All payment operations are logged in `payment_logs` table:

```php
PaymentLog::log($transaction, 'action', 'status', 'message', $data);
```

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review Laravel logs
3. Check payment logs in admin panel
4. Review webhook logs for errors

## License

This implementation is provided as-is for use in your Laravel application.
