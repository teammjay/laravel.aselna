<?php

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FetchResultFeedAsync;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SyncAllOrderStatusJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

//Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
//})->purpose('Display an inspiring quote')->hourly();


Schedule::command('logs:rotate')->everyFourHours();


Schedule::job(new AmazonListingItemSyncJob())->everyFiveMinutes();
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();
Schedule::command('app:result-feed-sync')->everyMinute();
Schedule::command('app:fetch-orders')->hourly();


Schedule::job(new EbayListingItemSyncJob())->hourly();
Schedule::job(new FetchWalmartOrdersJob())->hourly();
Schedule::job(new SyncAllOrderStatusJob())->hourly();
