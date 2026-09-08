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
                'name' => 'Store Type Sync',
                'description' => 'Sync all store types',
                'class_name' => 'App\Jobs\StoreTypeSyncJob',
            ],
            [
                'name' => 'Store Sync',
                'description' => 'Sync all stores',
                'class_name' => 'App\Jobs\StoreSyncJob',
            ],
            [
                'name' => 'Manufacturer Sync',
                'description' => 'Sync all manufacturers',
                'class_name' => 'App\Jobs\ManufacturerSyncJob',
            ],
            [
                'name' => 'Device Types Sync',
                'description' => 'Sync all device types',
                'class_name' => 'App\Jobs\DeviceTypesSyncJob',
            ],
            [
                'name' => 'Device Rating Sync',
                'description' => 'Sync all device ratings',
                'class_name' => 'App\Jobs\DeviceRatingSyncJob',
            ],
//            [
//                'name' => 'Device Series Sync',
//                'description' => 'Sync all device series',
//                'class_name' => 'App\Jobs\DeviceSeriesSyncJob',
//            ],
            [
                'name' => 'Brand Sync',
                'description' => 'Sync all brands',
                'class_name' => 'App\Jobs\BrandSyncJob',
            ],
            [
                'name' => 'Category Sync',
                'description' => 'Sync all categories',
                'class_name' => 'App\Jobs\CategorySyncJob',
            ],
            [
                'name' => 'Interface Sync',
                'description' => 'Sync all interfaces',
                'class_name' => 'App\Jobs\InterfaceSyncJob',
            ],
            [
                'name' => 'Devices Sync',
                'description' => 'Sync all devices',
                'class_name' => 'App\Jobs\DevicesSyncJob',
            ],
            [
                'name' => 'Device Interfaces',
                'description' => 'Sync all device interfaces',
                'class_name' => 'App\Jobs\DeviceInterfaces',
            ],
            [
                'name' => 'Product Condition Sync',
                'description' => 'Sync all product conditions',
                'class_name' => 'App\Jobs\ProductConditionJob',
            ],
            [
                'name' => 'Carrier Sync',
                'description' => 'Sync all carriers',
                'class_name' => 'App\Jobs\CarrierSyncJob',
            ],
            [
                'name' => 'Shipping Carrier Sync',
                'description' => 'Sync all shipping carriers',
                'class_name' => 'App\Jobs\ShippingCarrierJob',
            ],
            [
                'name' => 'Product Color Sync',
                'description' => 'Sync all product colors',
                'class_name' => 'App\Jobs\ProductColorSync',
            ],
            [
                'name' => 'Product Size Sync',
                'description' => 'Sync all product sizes',
                'class_name' => 'App\Jobs\ProductSizeSyncJob',
            ],
            [
                'name' => 'Product Material Sync',
                'description' => 'Sync all product
                materials',
                'class_name' => 'App\Jobs\ProductMaterialSyncJob',
            ],
            [
                'name' => 'Generation Type And Template Sync',
                'description' => 'Sync all generation types and templates',
                'class_name' => 'App\Jobs\GenerationTypeAndTemplateSync',
            ],
            [
                'name' => 'Product Sync',
                'description' => 'Sync all products',
                'class_name' => 'App\Jobs\Fonus\ProductSyncJob',
            ],
            [
                'name' => 'Product Channel Detail Sync',
                'description' => 'Sync all product channel details',
                'class_name' => 'App\Jobs\ProductChannelDetailSyncJob',
            ],
            [
                'name' => 'Store Product Sync',
                'description' => 'Sync all store products',
                'class_name' => 'App\Jobs\StoreProductSyncJob',
            ],
            [
                'name' => 'Product Interfaces',
                'description' => 'Sync all product interfaces',
                'class_name' => 'App\Jobs\ProductInterfaces',
            ],
            [
                'name' => 'Devices Link Sync',
                'description' => 'Sync all devices links',
                'class_name' => 'App\Jobs\DevicesLinkSyncJob',
            ],
            [
                'name' => 'Logistic Centre Sync',
                'description' => 'Sync all logistic centres',
                'class_name' => 'App\Jobs\LogisticCentreSyncJob',
            ],
            [
                'name' => 'Product Search Term Sync',
                'description' => 'Sync all product search terms',
                'class_name' => 'App\Jobs\ProductSearchTermSyncJob',
            ],
            [
                'name' => 'Product Type Keyword Sync',
                'description' => 'Sync all product type keywords',
                'class_name' => 'App\Jobs\ProductTypeKeywordSyncJob',
            ],
            [
                'name' => 'Product Tag Sync',
                'description' => 'Sync all product tags',
                'class_name' => 'App\Jobs\ProductTagSyncJob',
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
                'name' => 'Listing Template Sync',
                'description' => 'Sync all listing templates',
                'class_name' => 'App\Jobs\ListingTemplateSyncJob',
            ],
//            [
//                'name' => 'Store Pages Job Sync',
//                'description' => 'Sync all store pages',
//                'class_name' => 'App\Jobs\StorePagesJobSync',
//            ],
            [
                'name' => 'Device Carrier Sync',
                'description' => 'Sync all device carriers',
                'class_name' => 'App\Jobs\DeviceCarrierSyncJob',
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
                'name' => 'Listing Sync Job',
                'description' => 'Sync all listing items',
                'class_name' => 'App\Jobs\ListingSyncJob',
            ],
            [
                'name' => 'Listing Item Sync Job',
                'description' => 'Sync all listing items',
                'class_name' => 'App\Jobs\ListingItemSyncJob',
            ],
            [
                'name' => 'Listing Item Link sync Job',
                'description' => 'Sync all listing item links',
                'class_name' => 'App\Jobs\ListingItemLinkSyncJob',
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
                'name' => 'Addresses Sync Job',
                'description' => 'Sync all addresses',
                'class_name' => 'App\Jobs\AddressesSyncJob',
            ],
            [
                'name' => 'Shipping Account Sync Job',
                'description' => 'Sync all shipping accounts',
                'class_name' => 'App\Jobs\ShippingAccountsSyncJob',
            ],
            [
                'name' => 'Shipping Solution Sync Job',
                'description' => 'Sync all shipping solutions',
                'class_name' => 'App\Jobs\ShippingSolutionSyncJob',
            ],
            [
                'name' => 'Client Sync Job',
                'description' => 'Sync all clients',
                'class_name' => 'App\Jobs\ClientSyncJob',
            ],
            [
                'name' => 'Order Operation Reason Sync Job',
                'description' => 'Sync all order operation reasons',
                'class_name' => 'App\Jobs\OperationReasonSyncJob',
            ],
            [
                'name' => 'Messaging Account Sync Job',
                'description' => 'Sync all messaging account',
                'class_name' => 'App\Jobs\MessagingAccountSyncJob',
            ],
            [
                'name' => 'Store Message Account Sync Job',
                'description' => 'Sync all store message account',
                'class_name' => 'App\Jobs\StoreMessageAccountSyncJob',
            ],
//            [
//                'name' => 'Printer Sync Job',
//                'description' => 'Sync all printer',
//                'class_name' => 'App\Jobs\PrinterSyncJob',
//            ],
            [
                'name' => 'Shipment Document Setting Sync Job',
                'description' => 'Sync all shipment document settings',
                'class_name' => 'App\Jobs\ShipmentDocuemntSettingSyncJob',
            ],
            [
                'name' => 'HD Message Template Sync Job',
                'description' => 'Sync all hd message templates',
                'class_name' => 'App\Jobs\SyncMessageTemplateJob',
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
                //HtTicketStaticDataSyncJob
                'name' => 'HD Ticket Static Data Sync',
                'description' => 'Sync HD ticket static data',
                'class_name' => 'App\Jobs\HtTicketStaticDataSyncJob',
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
                //ShopifyListingProductsSyncJob
                'name' => 'Shopify Listing Products Sync',
                'description' => 'Sync Shopify listing products',
                'class_name' => 'App\Jobs\ShopifyListingProductsSyncJob',
            ],
            [
                //OldOrderSyncJob
                'name' => 'Old Order Sync',
                'description' => 'Sync old orders',
                'class_name' => 'App\Jobs\OldOrderSyncJob',
            ],
            [
                'name' => 'Shopify Pages Sync',
                'description' => 'Sync Shopify pages',
                'class_name' => 'App\Jobs\ShopifyPagesSyncJob',
            ],
            [
                //ShopifyStoreProductsSyncJob
                'name' => 'Shopify Store Products Sync',
                'description' => 'Sync Shopify store products',
                'class_name' => 'App\Jobs\ShopifyStoreProductsSyncJob',
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
//            [
//                //ShopifyCombinedSyncing
//                'name' => 'Shopify Combined Syncing',
//                'description' => 'Shopify Combined Syncing',
//                'class_name' => 'App\Jobs\Sync\ShopifyCombinedSyncing',
//            ],
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
                //ProductSpecificationSyncJob
                'name' => 'Product Specification Sync',
                'description' => 'Sync all product specifications',
                'class_name' => 'App\Jobs\ProductSpecificationSyncJob',
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
                //ProductKeyFeaturesSyncJob
                'name' => 'Product Key Features Sync',
                'description' => 'Sync all product key features',
                'class_name' => 'App\Jobs\ProductKeyFeaturesSyncJob',
            ],
            [
                //PopularDevicesSyncJob
                'name' => 'Popular Devices Sync',
                'description' => 'Sync all popular devices',
                'class_name' => 'App\Jobs\Devices\PopularDevicesSyncJob',
            ],
//            [
//                //CombinedDevicesSyncJob
//                'name' => 'Combined Devices Sync',
//                'description' => 'Combined Devices Sync',
//                'class_name' => 'App\Jobs\Sync\CombinedDevicesSyncJob',
//            ],
//            [
//                //CombinedProductsSyncJob
//                'name' => 'Combined Products Sync',
//                'description' => 'Combined Products Sync',
//                'class_name' => 'App\Jobs\Sync\CombinedProductsSyncJob',
//            ],
//            [
//                //CombinedSyncJob
//                'name' => 'Combined DB Sync',
//                'description' => 'Only Combined DB Sync',
//                'class_name' => 'App\Jobs\Sync\CombinedSyncJob',
//            ],
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
                'description' => 'Sync items for odemobile & accessry choice from oydera',
                'class_name' => 'App\Jobs\OdemobileListingItemSyncJob',
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
