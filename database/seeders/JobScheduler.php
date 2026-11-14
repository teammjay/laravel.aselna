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
            // Fonus (fonus_db) single-table syncs, in the order FonusCombinedSyncJob runs them.
            // Each one can be run on its own; later ones rely on rows the earlier ones wrote.
            [
                'name' => 'Fonus Product Color Sync',
                'description' => 'Sync product colors from Fonus (tbl_colors)',
                'class_name' => 'App\Jobs\Fonus\ProductcolorSyncJob',
            ],
            [
                'name' => 'Fonus Manufacturer Sync',
                'description' => 'Sync manufacturers from Fonus (tbl_manufacturer)',
                'class_name' => 'App\Jobs\Fonus\ManufacturerSyncJob',
            ],
            [
                'name' => 'Fonus Carrier Sync',
                'description' => 'Sync mobile carriers from Fonus (tbl_carrier)',
                'class_name' => 'App\Jobs\Fonus\CarrierSyncJob',
            ],
            [
                'name' => 'Fonus Product Sync',
                'description' => 'Sync the product catalog and product key features from Fonus (tbl_product)',
                'class_name' => 'App\Jobs\Fonus\ProductSyncJob',
            ],
            [
                'name' => 'Fonus Interface Sync',
                'description' => 'Sync compatibility interfaces (attributes) from Fonus (tbl_attributes)',
                'class_name' => 'App\Jobs\Fonus\InterfacesSyncJob',
            ],
            [
                'name' => 'Fonus Category Sync',
                'description' => 'Sync product categories and sub-categories from Fonus (tbl_category, tbl_category_sub)',
                'class_name' => 'App\Jobs\Fonus\CategorySyncJob',
            ],
            [
                'name' => 'Fonus Brand Sync',
                'description' => 'Sync device brands from Fonus (tbl_brand)',
                'class_name' => 'App\Jobs\Fonus\BrandSyncJob',
            ],
            [
                'name' => 'Fonus Device Type Sync',
                'description' => 'Sync device types and sub-types from Fonus (tbl_device_type, tbl_device_type_sub)',
                'class_name' => 'App\Jobs\Fonus\DeviceTypeSyncJob',
            ],
            [
                'name' => 'Fonus Device Sync',
                'description' => 'Sync devices and gadgets from Fonus (tbl_device, tbl_gadget)',
                'class_name' => 'App\Jobs\Fonus\DevicesSyncJob',
            ],
            [
                'name' => 'Fonus Device Carrier Sync',
                'description' => 'Sync which carriers each device is sold on from Fonus (tbl_device_carriers)',
                'class_name' => 'App\Jobs\Fonus\DeviceCarrierSyncJob',
            ],
            [
                'name' => 'Fonus Device Interface Sync',
                'description' => 'Sync device and gadget to interface links from Fonus (tbl_device_attributes, tbl_gadget_attributes)',
                'class_name' => 'App\Jobs\Fonus\DevicesInterfacesSyncJob',
            ],
            [
                'name' => 'Fonus Product Interface Sync',
                'description' => 'Sync product to interface links from Fonus (tbl_product)',
                'class_name' => 'App\Jobs\Fonus\ProductInterfacesSyncJob',
            ],
            [
                'name' => 'Fonus Title Keyword Sync',
                'description' => 'Sync product title keywords from Fonus (tbl_title_keywords)',
                'class_name' => 'App\Jobs\Fonus\ProductTitleKeyword',
            ],
            [
                'name' => 'Fonus Product Channel Sync',
                'description' => 'Sync per-channel product details from Fonus (tbl_product_channels)',
                'class_name' => 'App\Jobs\Fonus\ProductChannelSyncJob',
            ],
            [
                'name' => 'Fonus Popular Devices Sync',
                'description' => 'Sync the popular devices list from Fonus (tbl_popular_devices)',
                'class_name' => 'App\Jobs\Fonus\PopularDevicesSyncJob',
            ],
            [
                'name' => 'Fonus Product Specifications Sync',
                'description' => 'Sync product specifications and their values from Fonus (tbl_features, tbl_category_feature_values, tbl_product_features)',
                'class_name' => 'App\Jobs\Fonus\ProductSpecificationsSyncJob',
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
                'name' => 'Walmart Pending Inventory Sync',
                'description' => 'Send inventory for live Walmart items that never had an inventory feed',
                'class_name' => 'App\Jobs\Walmart\PendingInventorySyncJob',
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
                'name' => 'Fonus Combined Cron Job',
                'description' => 'Combined Fonus Scheduler Cron Job',
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
                'description' => 'Sync store products, listing items, listing item details, listing item images & navigation pages from oydera, then fetch the Shopify products, for uzid, odemobile & accessorychoice',
                'class_name' => 'App\Jobs\Sync\OyderaMasterSwitch',
            ],
            [
                //SyncShopifyDeviceSkuMetafieldsJob
                'name' => 'Shopify Device SKU Metafields',
                'description' => 'Push the product+device combination SKUs to the global.device_skus metafield of each Shopify product (also runs after every Shopify feed generation)',
                'class_name' => 'App\Jobs\Shopify\SyncShopifyDeviceSkuMetafieldsJob',
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
