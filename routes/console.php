<?php

use App\Jobs\AmazonListingItemSyncJob;
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
Schedule::job(new AmazonListingItemSyncJob())->everyFiveMinutes();
Schedule::job(new EbayListingItemSyncJob())->hourly();
//Schedule::job(new WalmartListingItemSyncJob())->hourly();

// Listing Feed Result Syncing
Schedule::job(new FetchResultFeedAsync())->everyFiveMinutes();

//Order Syncing
Schedule::job(new FetchWalmartOrdersJob())->hourly();
Schedule::job(new FetchEbayOrders())->hourly();
Schedule::job(new FetchShopifyOrders())->hourly();
Schedule::job(new SyncAllOrderStatusJob())->hourly();


// Email Message fetching
Schedule::job(FetchEmailMessagesJob::class)->everyTenMinutes();

// Products Syncing
Schedule::job(new SubmitShopifyProductsJob())->hourly();

//Category Syncing
Schedule::job(new ShopifyCollectionSyncJob())->dailyAt('03:00');

//Devices, Interfaces, and Brands Syncing at 1 2 and 3 AM
Schedule::job(new ProductSyncJob())->dailyAt('00:30');
Schedule::job(new InterfaceSyncJob())->dailyAt('01:00');
Schedule::job(new DeviceSeriesSyncJob())->dailyAt('01:00');
Schedule::job(new CategorySyncJob())->dailyAt('01:30');
Schedule::job(new BrandSyncJob())->dailyAt('02:00');
Schedule::job(new DevicesSyncJob())->dailyAt('02:30');
Schedule::job(new ProductImagesSyncJob())->dailyAt('04:00');
Schedule::job(new DeviceInterfaces())->dailyAt('03:30');
Schedule::job(new ProductInterfaces())->dailyAt('04:30');
Schedule::job(new DeviceCarrierSyncJob())->dailyAt('05:30');



Schedule::call(function () {
    // Delete all the records from yesterday to all previous days
    OrderSynchronization::where('updated_at', '<', now()->toDate())->delete();
})->dailyAt('13:00')->name('clear syncronisation');
