<?php

namespace App\Jobs;

use App\Services\CryptoPaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExpiredPayments implements ShouldQueue
{
    use Queueable;

    public function handle(CryptoPaymentService $paymentService): void
    {
        $paymentService->checkExpiredPayments();
    }
}
