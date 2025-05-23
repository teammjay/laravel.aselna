<?php

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FetchResultFeedAsync;
use App\Jobs\FetchWalmartOrdersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();


Schedule::command('logs:rotate')->everyFourHours();


Schedule::job(new AmazonListingItemSyncJob())->everyFiveMinutes();
Schedule::job(new EbayListingItemSyncJob())->everyFiveMinutes();
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();
Schedule::command('app:result-feed-sync')->everyMinute();

//Schedule::job(new FetchWalmartOrdersJob())->hourly();
