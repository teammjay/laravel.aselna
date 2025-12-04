<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\ListingItemSyncJob;
use App\Jobs\ListingSyncJob;
use App\Jobs\LogisticCentreSyncJob;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('logs:rotate')->everyOddHour();
        $schedule->command('app:result-feed-sync')->everyMinute();
        $schedule->command('app:run-daily-update-listing-sales')->twiceDaily(2, 14);
//        $schedule->job(new ListingItemSyncJob())->daily();
//        $schedule->job(new ListingSyncJob())->daily();
//        $schedule->job(new LogisticCentreSyncJob())->daily();

        $jobs = \App\Models\ScheduledJobs::where('is_active', true)->get();

        foreach ($jobs as $job) {
            $class = $job->job->class_name;

            if (!class_exists($class)) continue;

            $params = json_decode($job->parameters, true) ?? [];

            $schedule->job(new $class(...array_values($params)))
                ->cron($job->cron_expression)
                ->name("{$job->name}");
        }

        /*$jobs = \App\Models\ScheduledJobs::where('is_active', 1)->get();

        foreach ($jobs as $job) {
            if (class_exists($job->class_name)) {
                $class = $job->class_name;
                if (class_exists($class)) {
                    $schedule->job(app($class))
                        ->cron($job->cron_expression)
                        ->name($job->name);
                }else{
                    Log::warning("Scheduled job class not found: {$class}");
                }
            }
        }*/
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
