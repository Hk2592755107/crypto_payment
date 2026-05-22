<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

Route::post('/webhooks/nowpayments', [WebhookController::class, 'nowpayments'])->name('webhooks.nowpayments');
