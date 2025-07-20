<?php

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FetchEbayOrders;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchResultFeedAsync;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\SubmitShopifyProductsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('logs:rotate')->everyFourHours();


// Listing sync Job
Schedule::job(new AmazonListingItemSyncJob())->everyFiveMinutes();
Schedule::job(new EbayListingItemSyncJob())->hourly();

// Listing Feed Result Syncing
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();

//Order Syncing
Schedule::job(new FetchWalmartOrdersJob())->hourly();
Schedule::job(new FetchEbayOrders())->hourly();
Schedule::job(new FetchShopifyOrders())->hourly();
Schedule::job(new SyncAllOrderStatusJob())->hourly();


// Email Message fetching
Schedule::job(FetchEmailMessagesJob::class)->everyThirtyMinutes();

// Products Syncing
//Schedule::job(new SubmitShopifyProductsJob())->everyFifteenMinutes();
