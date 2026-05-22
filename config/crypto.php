<?php

return [
    'sandbox_mode' => env('CRYPTO_SANDBOX_MODE', true) === true || env('CRYPTO_SANDBOX_MODE', true) === 'true',

    'nowpayments_api_key' => env('NOWPAYMENTS_API_KEY'),
    'nowpayments_webhook_secret' => env('NOWPAYMENTS_WEBHOOK_SECRET'),

    'payment_timeout' => (int) env('CRYPTO_PAYMENT_TIMEOUT', 30),
    'qr_code_size' => env('CRYPTO_QR_CODE_SIZE', '300x300'),

    'payment_statuses' => [
        'pending' => 'Waiting for payment',
        'waiting_confirmation' => 'Waiting for blockchain confirmation',
        'confirmed' => 'Payment confirmed',
        'failed' => 'Payment failed',
        'expired' => 'Payment expired',
    ],
];
