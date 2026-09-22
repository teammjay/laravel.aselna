<?php

namespace Database\Seeders;

use App\Models\ScheduledJob;
use Illuminate\Database\Seeder;

class JobScheduler extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jobs = [
            [
                'name' => 'Product Sync',
                'description' => 'Sync all products',
                'class_name' => 'App\Jobs\Fonus\ProductSyncJob',
            ],
            [
                'name' => 'Product Images Sync',
                'description' => 'Sync all product images',
                'class_name' => 'App\Jobs\ProductImagesSyncJob',
            ],
            [
                'name' => 'Product Alternative Images Sync',
                'description' => 'Sync all product alternative images',
                'class_name' => 'App\Jobs\ProductAlternativeImagesSyncJob',
            ],
            [
                'name' => 'Fetch Result Feed Async',
                'description' => 'Fetch result feed asynchronously',
                'class_name' => 'App\Jobs\FetchResultFeedAsync',
            ],
            [
                'name' => 'Amazon Product Type Sync',
                'description' => 'Sync all Amazon product types',
                'class_name' => 'App\Jobs\AmazonProductTypeSyncJob',
            ],
            [
                'name' => 'Fetch Walmart Orders Sync Job',
                'description' => 'Fetch Walmart orders',
                'class_name' => 'App\Jobs\FetchWalmartOrdersJob',
            ],
            [
                'name' => 'Fetch Shopify Orders Sync Job',
                'description' => 'Fetch Shopify orders',
                'class_name' => 'App\Jobs\FetchShopifyOrders',
            ],
            [
                'name' => 'Fetch Ebay Orders Sync Job',
                'description' => 'Fetch Ebay orders',
                'class_name' => 'App\Jobs\FetchEbayOrders',
            ],
            [
                'name' => 'Sync all order status',
                'description' => 'Sync all order status',
                'class_name' => 'App\Jobs\SyncAllOrderStatusJob',
            ],
            [
                'name' => 'System Printer Sync Job',
                'description' => 'Sync all system printers',
                'class_name' => 'App\Jobs\SystemPrinterSyncJob',
            ],
            [
                'name' => 'Submit Product to Shopify Store',
                'description' => 'Submit Product to Shopify Store',
                'class_name' => 'App\Jobs\SubmitShopifyProductsJob',
            ],
            [
                'name' => 'Magento Products Sync',
                'description' => 'Sync all Magento products',
                'class_name' => 'App\Jobs\Magento\MagentoProductsSyncJob',
            ],
            [
                //FetchShopifyProductsJob
                'name' => 'Fetch Shopify Products',
                'description' => 'Fetch Shopify products',
                'class_name' => 'App\Jobs\FetchShopifyProductsJob',
            ],
            [
                'name' => 'Fetch Email Message Sync Job',
                'description' => 'Fetch Email Message',
                'class_name' => 'App\Jobs\FetchEmailMessagesJob',
            ],
            [
                //FetchMagentoProductsJob
                'name' => 'Fetch Magento Products',
                'description' => 'Fetch Magento products',
                'class_name' => 'App\Jobs\Magento\FetchMagentoProductsJob',
            ],
            [
                //ShopifyCollectionSyncJob
                'name' => 'Shopify Collection Sync',
                'description' => 'Sync Shopify collections',
                'class_name' => 'App\Jobs\ShopifyCollectionSyncJob',
            ],
            [
                'name' => 'Shopify Pages Sync',
                'description' => 'Sync Shopify pages',
                'class_name' => 'App\Jobs\ShopifyPagesSyncJob',
            ],
            [
                'name' => 'Transaction Status Update Job',
                'description' => 'Transaction Status Update Job',
                'class_name' => 'App\Jobs\TransactionStatusCheckJob',
            ],
            [
                'name' => 'Walmart Listing Item Status Check',
                'description' => 'Walmart Listing Item Status Check',
                'class_name' => 'App\Jobs\WalmartListingItemSyncJob',
            ],
            [
                'name' => 'Send Notification Email Sync Fail',
                'description' => 'Send Notification Email Sync Fail',
                'class_name' => 'App\Jobs\SendNotificationEmailSyncFailJob',
            ],
            [
                'name' => 'Ebay Listing Item Status Check',
                'description' => 'Ebay Listing Item Status Check',
                'class_name' => 'App\Jobs\EbayListingItemSyncJob',
            ],
            [
                'name' => 'Listing Item Detail Status Check',
                'description' => 'Listing Item Detail Status Check',
                'class_name' => 'App\Jobs\ListingItemDetailStatusCheckJob',
            ],
            [
                //FetchTemuOrders
                'name' => 'Fetch Temu Orders Sync Job',
                'description' => 'Fetch Temu orders',
                'class_name' => 'App\Jobs\FetchTemuOrders',
            ],
            [
                //FetchTemuOrders
                'name' => 'Amazon listing item status check',
                'description' => 'Amazon Listing Item Status Check',
                'class_name' => 'App\Jobs\AmazonListingItemSyncJob',
            ],
            [
                //ExistingListingItemSyncJob
                'name' => 'Walmart Existing Listing Item Sync',
                'description' => 'Walmart Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Walmart\ExistingListingItemSyncJob'
            ],
            [
                //ExistingListingItemSyncJob
                'name' => 'eBay Existing Listing Item Sync',
                'description' => 'eBay Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Ebay\ExistingListingItemSyncJob'
            ],
            [
                //ExistingListingItemSyncJob
                'name' => 'Amazon Existing Listing Item Sync',
                'description' => 'Amazon Existing Listing Item Sync',
                'class_name' => 'App\Jobs\Amazon\ExistingListingItemSyncJob'
            ],
            [
                //ShopifyOnlySyncJob
                'name' => 'Shopify Only Sync Job',
                'description' => 'Shopify Only Sync Job',
                'class_name' => 'App\Jobs\Sync\ShopifyOnlySyncJob',
            ],
            [
                //FonusCombinedSyncJob
                'name' => 'Fonus Scheduler Cron Job',
                'description' => 'Fonus Scheduler Cron Job',
                'class_name' => 'App\Jobs\Sync\FonusCombinedSyncJob',
            ],
            [
                //OdemobileListingItemSyncJob
                'name' => 'Odemobile Items Sync Job',
                'description' => 'Sync items for uzid & odemobile & accessry choice from oydera',
                'class_name' => 'App\Jobs\OdemobileListingItemSyncJob',
            ],
            [
                //OyderaMasterSwitch
                'name' => 'Oydera Master Switch',
                'description' => 'Sync store products, listing items, listing item details & listing item images from oydera for uzid, odemobile & accessorychoice',
                'class_name' => 'App\Jobs\Sync\OyderaMasterSwitch',
            ]
        ];

        // delete all existing scheduled jobs which we can be delete without foreign key issues
        ScheduledJob::whereNotIn('class_name', array_map(fn($job) => $job['class_name'], $jobs))->delete();

        foreach ($jobs as $job) {
            ScheduledJob::updateOrCreate([
                'class_name' => $job['class_name'],
            ], $job);
        }
    }
}
