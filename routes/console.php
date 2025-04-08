<?php

use App\Jobs\AmazonListingItemSyncJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('logs:rotate')->everyFourHours();
Schedule::command('app:result-feed-sync')->everyMinute();

Schedule::job(new AmazonListingItemSyncJob())->everyFiveMinutes();
