<?php

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FetchEbayOrders;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchResultFeedAsync;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchTemuOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Jobs\Sync\ShopifyCombinedSyncing;
use App\Jobs\Sync\ShopifyOnlySyncJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\TransactionStatusCheckJob;
use App\Jobs\WalmartListingItemSyncJob;
use App\Models\OrderSynchronization;
use Illuminate\Support\Facades\Schedule;


Schedule::command('logs:rotate')->everyFourHours();
Schedule::command('app:run-daily-update-listing-sales')->everyFourHours();


// Listing sync Job
Schedule::job(new AmazonListingItemSyncJob())->hourly();
Schedule::job(new EbayListingItemSyncJob())->hourly();
Schedule::job(new WalmartListingItemSyncJob())->hourly();

Schedule::job(new App\Jobs\Walmart\ExistingListingItemSyncJob())->dailyAt('01:00');
Schedule::job(new App\Jobs\Ebay\ExistingListingItemSyncJob())->dailyAt('01:00');
Schedule::job(new App\Jobs\Amazon\ExistingListingItemSyncJob())->dailyAt('01:00');

// Listing Feed Result Syncing
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();

//Order Syncing
Schedule::job(new FetchWalmartOrdersJob())->everyThirtyMinutes();
Schedule::job(new FetchEbayOrders())->everyThirtyMinutes();
Schedule::job(new FetchShopifyOrders())->everyThirtyMinutes();
Schedule::job(new SyncAllOrderStatusJob())->everyThirtyMinutes();
Schedule::job(new FetchTemuOrders())->everyThirtyMinutes();

// I want to run this at every 45 Minute of the hour
//Schedule::job(new SendNotificationEmailSyncFailJob(2))->cron('12 * * * *');
//Schedule::job(new SendNotificationOrderSyncFailJob(2))->cron('12 * * * *');

// Email Message fetching
Schedule::job(FetchEmailMessagesJob::class)->everyTenMinutes();

// Products Syncing
Schedule::job(new SubmitShopifyProductsJob())->cron('*/5 * * * *');
Schedule::job(new ShopifyOnlySyncJob())->cron('10 */2 * * *');

Schedule::job(new ShopifyCombinedSyncing())->dailyAt('12:30');



Schedule::call(function () {
    // Delete all the records from yesterday to all previous days
    OrderSynchronization::where('updated_at', '<', now()->toDate())->delete();
})->dailyAt('00:00')->name('clear syncronisation');


Schedule::job(new TransactionStatusCheckJob())->hourly();
