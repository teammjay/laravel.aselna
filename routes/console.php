<?php

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\SendNotificationEmailSyncFailJob;
use App\Jobs\SendNotificationOrderSyncFailJob;
use App\Jobs\ShopifyCombinedSyncing;
use App\Jobs\WalmartListingItemSyncJob;
use App\Jobs\BrandSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DeviceSeriesSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FetchEbayOrders;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchResultFeedAsync;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductSyncJob;
use App\Jobs\ShopifyCollectionSyncJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Models\OrderSynchronization;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command('logs:rotate')->everyFourHours();


// Listing sync Job
Schedule::job(new AmazonListingItemSyncJob())->hourly();
Schedule::job(new EbayListingItemSyncJob())->hourly();
Schedule::job(new WalmartListingItemSyncJob())->hourly();

// Listing Feed Result Syncing
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();

//Order Syncing
Schedule::job(new FetchWalmartOrdersJob())->hourly();
Schedule::job(new FetchEbayOrders())->hourly();
Schedule::job(new FetchShopifyOrders())->hourly();
Schedule::job(new SyncAllOrderStatusJob())->hourly();

// I want to run this at every 45 Minute of the hour
Schedule::job(new SendNotificationEmailSyncFailJob(2))->cron('4,34 * * * *');
Schedule::job(new SendNotificationOrderSyncFailJob(2))->cron('4,34 * * * *');

// Email Message fetching
Schedule::job(FetchEmailMessagesJob::class)->everyTenMinutes();

// Products Syncing
Schedule::job(new SubmitShopifyProductsJob())->hourly();

Schedule::job(new ShopifyCombinedSyncing())->dailyAt('12:30');



Schedule::call(function () {
    // Delete all the records from yesterday to all previous days
    OrderSynchronization::where('updated_at', '<', now()->toDate())->delete();
})->dailyAt('13:00')->name('clear syncronisation');
