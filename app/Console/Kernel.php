<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ListingItemSyncJob;
use App\Jobs\ListingSyncJob;
use App\Jobs\LogisticCentreSyncJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('logs:rotate')->everyOddHour();
        $schedule->command('app:result-feed-sync')->everyMinute();
//        $schedule->job(new ListingItemSyncJob())->daily();
//        $schedule->job(new ListingSyncJob())->daily();
//        $schedule->job(new LogisticCentreSyncJob())->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
