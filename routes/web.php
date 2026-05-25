<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Admin\CryptoGatewayController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function () {
    if (Auth::attempt(request()->only('email', 'password'))) {
        return redirect('/');
    }
    return back()->withErrors(['email' => 'Invalid credentials']);
});

Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function () {
    $user = \App\Models\User::create([
        'name' => request('name'),
        'email' => request('email'),
        'password' => bcrypt(request('password')),
    ]);
    Auth::login($user);
    return redirect('/');
});

Route::prefix('payments')->name('payments.')->group(function () {
    Route::get('checkout/{order}', [PaymentController::class, 'checkout'])->name('checkout');
    Route::post('pay/{order}', [PaymentController::class, 'pay'])->name('pay');
    Route::get('confirm/{transaction}', [PaymentController::class, 'confirm'])->name('confirm');
    Route::get('status/{transaction}', [PaymentController::class, 'status'])->name('status');
    Route::get('success', [PaymentController::class, 'success'])->name('success');
    Route::get('cancel', [PaymentController::class, 'cancel'])->name('cancel');
});

use App\Http\Controllers\WebhookController;

Route::post('/webhooks/nowpayments', [WebhookController::class, 'nowpayments'])
    ->name('webhooks.nowpayments');

Route::prefix('admin')->middleware('auth')->name('admin.')->group(function () {
    Route::resource('crypto-gateways', CryptoGatewayController::class, [
        'parameters' => ['crypto-gateway' => 'crypto_gateway'],
        'except' => ['show'],
    ]);
    Route::get('crypto-gateways/{crypto_gateway}/transactions', [CryptoGatewayController::class, 'transactions'])->name('crypto-gateways.transactions');
    Route::get('crypto-gateways/{crypto_gateway}/webhook-logs', [CryptoGatewayController::class, 'webhookLogs'])->name('crypto-gateways.webhook-logs');
});
