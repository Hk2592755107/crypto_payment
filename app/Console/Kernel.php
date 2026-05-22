<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\SyncCryptoPaymentStatus;
use App\Jobs\CheckExpiredPayments;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->job(new SyncCryptoPaymentStatus)->everyMinute();
        $schedule->job(new CheckExpiredPayments)->everyFiveMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
