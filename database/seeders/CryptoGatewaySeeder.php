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
            'is_active' => false,
            'priority' => 1,
            'transaction_fee_percentage' => 1.0,
            'min_transaction_amount' => 0.01,
            'max_transaction_amount' => null,
            'confirmation_required' => 1,
            'config' => [
                'api_key' => env('COINBASE_COMMERCE_API_KEY', ''),
                'webhook_secret' => env('COINBASE_COMMERCE_WEBHOOK_SECRET', ''),
            ],
        ]);

        CryptoGateway::create([
            'name' => 'NOWPayments',
            'slug' => 'nowpayments',
            'description' => 'Accept crypto payments via NOWPayments',
            'api_endpoint' => 'https://api.nowpayments.io/v1',
            'webhook_endpoint' => env('APP_URL') . '/webhooks/nowpayments',
            'supported_currencies' => ['BTC', 'ETH', 'USDT', 'USDC', 'BNB', 'LTC', 'DOGE', 'XRP'],
            'is_active' => false,
            'priority' => 2,
            'transaction_fee_percentage' => 0.5,
            'min_transaction_amount' => 0.01,
            'max_transaction_amount' => null,
            'confirmation_required' => 1,
            'config' => [
                'api_key' => env('NOWPAYMENTS_API_KEY', ''),
                'webhook_secret' => env('NOWPAYMENTS_WEBHOOK_SECRET', ''),
            ],
        ]);
    }
}
