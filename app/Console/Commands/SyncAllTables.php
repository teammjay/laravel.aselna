<?php

namespace App\Console\Commands;

use App\Constants\WebStorePageType;
use App\Jobs\Ebay\ExistingListingItemSyncJob;
use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\AmazonProductTypeSyncJob;
use App\Jobs\BrandSyncJob;
use App\Jobs\CarrierSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DataPatch;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DeviceRatingSyncJob;
use App\Jobs\Devices\PopularDevicesSyncJob;
use App\Jobs\DeviceSeriesSyncJob;
use App\Jobs\DevicesLinkSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\DeviceTypesSyncJob;
use App\Jobs\EbayListingItemSyncJob;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchAmazonOrdersJob;
use App\Jobs\FetchEbayOrders;
use App\Jobs\FetchEmailMessagesJob;
use App\Jobs\FetchMagentoCategoriesJob;
use App\Jobs\FetchMagentoProductsJob;
use App\Jobs\FetchShopifyOrders;
use App\Jobs\FetchShopifyProductsJob;
use App\Jobs\FetchTemuOrders;
use App\Jobs\FetchWalmartOrdersJob;
use App\Jobs\FileSystemSyncJob;
use App\Jobs\GenerationTypeAndTemplateSync;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ListingFeedSyncJob;
use App\Jobs\ListingItemDataSyncJob;
use App\Jobs\ListingItemImageCreate;
use App\Jobs\ListingItemLinkSyncJob;
use App\Jobs\ListingItemProductImageSyncJob;
use App\Jobs\ListingItemSyncJob;
use App\Jobs\ListingSyncJob;
use App\Jobs\ListingTemplateSyncJob;
use App\Jobs\LogisticCentreSyncJob;
use App\Jobs\MagentoProductsSyncJob;
use App\Jobs\MagnetoAttributesSyncJob;
use App\Jobs\ManufacturerSyncJob;
use App\Jobs\OldOrderSyncJob;
use App\Jobs\ProcessFeedGenerationJob;
use App\Jobs\ProductAlternativeImagesSyncJob;
use App\Jobs\ProductChannelDetailSyncJob;
use App\Jobs\ProductColorSync;
use App\Jobs\ProductConditionJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductKeyFeaturesSyncJob;
use App\Jobs\ProductMaterialSyncJob;
use App\Jobs\ProductSearchTermSyncJob;
use App\Jobs\ProductSizeSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\ProductTagSyncJob;
use App\Jobs\ProductTypeKeywordSyncJob;
use App\Jobs\SendNotificationEmailSyncFailJob;
use App\Jobs\SendNotificationOrderSyncFailJob;
use App\Jobs\ShippingCarrierJob;
use App\Jobs\Shopify\SyncShopifyItems;
use App\Jobs\ShopifyCollectionDeviceSyncJob;
use App\Jobs\ShopifyCollectionSyncJob;
use App\Jobs\ShopifyCombinedSyncing;
use App\Jobs\ShopifyDuplicateStoreProductsJob;
use App\Jobs\ShopifyIndvPagesSyncJob;
use App\Jobs\ShopifyListingProductsSyncJob;
use App\Jobs\ShopifyPagesSyncJob;
use App\Jobs\ShopifyStoreProductsSyncJob;
use App\Jobs\StoreCategorySyncJob;
use App\Jobs\StorePagesJobSync;
use App\Jobs\StoreProductSyncJob;
use App\Jobs\StoreSyncJob;
use App\Jobs\StoreTypeListingSyncJob;
use App\Jobs\StoreTypeSyncJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Jobs\Sync\FonusCombinedSyncJob;
use App\Jobs\SyncAllOrderStatusJob;
use App\Jobs\synccategorytospecification;
use App\Jobs\SyncFailedMarketplaceShipmentJob;
use App\Jobs\SystemPrinterSyncJob;
use App\Jobs\TransactionStatusCheckJob;
use App\Jobs\UPCGenerateJob;
use App\Jobs\UPCSyncJob;
use App\Models\DeviceSeries;
use App\Models\ListingTemplate;
use App\Models\ProductMaterial;
use App\Models\ProductSize;
use App\Models\ProductType;
use App\Utils\EndicialabelService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class SyncAllTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-all-tables';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start All the commands to sync all the tables';

    /**
     * Execute the console command.
     */
    public function handle()
    {
//        SyncFailedMarketplaceShipmentJob::dispatch( 'c431af0e-a0df-4072-afd3-f9258705a9ba');
//         Bus::chain([
//            new FileSystemSyncJob(),
//            new StoreTypeSyncJob(),
//           new StoreSyncJob(),
//            new ManufacturerSyncJob(),
//            new DeviceTypesSyncJob(),
//            new DeviceRatingSyncJob(),
//            new DeviceSeriesSyncJob(),
//            new BrandSyncJob(),
        //    new CategorySyncJob(),
//            new InterfaceSyncJob(),
//            new DevicesSyncJob(),
//            new DeviceInterfaces(),
//            new ProductConditionJob(),
//           new CarrierSyncJob(),
//            new ShippingCarrierJob(),
//            new ProductColorSync(),
        //    new ProductSizeSyncJob(),
        //    new ProductMaterialSyncJob(),
//            new GenerationTypeAndTemplateSync(),
//            new ProductSyncJob(),
//            new ProductChannelDetailSyncJob(),
//            new StoreProductSyncJob(),
//            new ProductInterfaces(),
//            new DevicesLinkSyncJob(),
//            new LogisticCentreSyncJob(),
//            new ProductSearchTermSyncJob(),
//            new ProductTypeKeywordSyncJob(),
//            new ProductTagSyncJob(),
        // new ProductImagesSyncJob(),
//            new ProductAlternativeImagesSyncJob(),
        //    new ListingTemplateSyncJob(),
        // new StorePagesJobSync(),
//            new DeviceCarrierSyncJob(),
        // new ProductSpecificationSyncJob()
//         ])->dispatch();
//        Bus::chain([
//            ListingSyncJob::dispatch(),
//            ListingItemSyncJob::dispatch(),
//            ListingItemDataSyncJob::dispatch(),
//            ListingItemLinkSyncJob::dispatch(),
//            ListingItemProductImageSyncJob::dispatch();
//            StoreTypeListingSyncJob::dispatch(),
//            ListingFeedSyncJob::dispatch(),
//            UPCSyncJob::dispatch(),
//            LogisticCentreSyncJob::dispatch(),
//            ProductImagesSyncJob::dispatch();
//            ProductAlternativeImagesSyncJob::dispatch();
//        ]);
//        DataPatch::dispatch();
//           FeedGenerationJob::dispatch('9043a2c1-0b2d-4a9e-89bd-c08a02f6637c');
        //    FeedGenerationJob::dispatch('4e7f02b9-3530-4e87-ad72-8cf5881ebe66');
//        ListingItemImageCreate::dispatch('45a0b556-f1f8-445f-a83f-498ee612e680');
//       FeedGenerationJob::dispatch('d84b01c5-30a1-410d-9e08-cad952c21e9f');
        //    ListingItemImageCreate::dispatch('4dcf1d3f-b723-4b31-a075-3702c0746b10');
//        FetchAmazonOrdersJob::dispatch();
//        StoreCategorySyncJob::dispatch('94428796-cdf9-dc2b-a1e7-aa4100066fd9');
//        ShopifyCollectionDeviceSyncJob::dispatch('5cc46182-9318-7a47-39bd-d3e38de6cce6');
//        AmazonListingItemSyncJob::dispatch();
//        AmazonProductTypeSyncJob::dispatch();
//        FetchWalmartOrdersJob::dispatch();
        //synccategorytospecification::dispatch();
//        SystemPrinterSyncJob::dispatch();
//        ShopifyIndvPagesSyncJob::dispatch('5cc46182-9318-7a47-39bd-d3e38de6cce6', WebStorePageType::ALL_BRANDS);
//        ShopifyCombinedSyncing::dispatch();

//        SendNotificationEmailSyncFailJob::dispatch(2);
//        SendNotificationOrderSyncFailJob::dispatch(2);
//        FetchEmailMessagesJob::dispatch();
//        InterfaceSyncJob::dispatch();

//        FetchEbayOrders::dispatch();
        FetchEmailMessagesJob::dispatch();
//        SubmitShopifyProductsJob::dispatch();
//        FetchShopifyProductsJob::dispatch();
//        FetchMagentoProductsJob::dispatch();
//        MagentoProductsSyncJob::dispatch();
//        FetchMagentoCategoriesJob::dispatch();
//        MagnetoAttributesSyncJob::dispatch();
//        ShopifyPagesSyncJob::dispatch();
//        SubmitShopifyProductsJob::dispatch();
//        ShopifyListingProductsSyncJob::dispatch();
//        ShopifyStoreProductsSyncJob::dispatch();
//        ProcessFeedGenerationJob::dispatch(json_decode('{"store_id":"3fab8878-e036-58c7-db30-addd3058875c","feed_type":"Google","fileType":"csv","product_code":["J14"],"device":null,"interface":null,"device_series":null,"fileName":"google_shopping_feed_2025-08-26-09-15-19.csv","filePath":"feeds\/google-shopping\/google_shopping_feed_2025-08-26-09-15-19.csv"}',true), 'fd066ad3-7059-4a87-a6e7-a18e5823f016');
//        ShopifyDuplicateStoreProductsJob::dispatch('6c7009ce-eac1-a995-a986-27d5df0103ac', '47338682-e00d-4ab2-9db6-76343bb96162');
//        ShopifyDuplicateStoreProductsJob::dispatch('c2c8131f-c8a2-e082-9f89-3e8bf96b1049', 'd51981a2-bb52-4b8c-bd17-3836d65d2bb4');
//        UPCGenerateJob::dispatch(
//            [
//                'store' => '6f535170-ecf1-7fda-9b5e-c9045278bb47',
//                'company_prefix' => '727020',
//                'product_number' => 50000,
//                'number_of_codes' => 20000
//            ]
//        );
//        ShopifyIndvPagesSyncJob::dispatch('47338682-e00d-4ab2-9db6-76343bb96162', WebStorePageType::BRAND_DEVICES);
//        DevicesSyncJob::dispatch();
//        DeviceInterfaces::dispatch();
//        SyncAllOrderStatusJob::dispatch();
//        FetchTemuOrders::dispatch();
//        DevicesSyncJob::dispatch();
//        ProductImagesSyncJob::dispatch();
//        FetchEmailMessagesJob::dispatch();
//        EbayListingItemSyncJob::dispatch();
//        AmazonListingItemSyncJob::dispatch();
//        TransactionStatusCheckJob::dispatch();
//        ProductKeyFeaturesSyncJob::dispatch();
//        ShopifyCombinedSyncing::dispatch();
//        PopularDevicesSyncJob::dispatch();
//        ProductImagesSyncJob::dispatch();
//        ListingItemProductImageSyncJob::dispatch();
//        CategorySyncJob::dispatch();
//        SyncShopifyItems::dispatch();
//        ExistingListingItemSyncJob::dispatch();
//        FetchShopifyOrders::dispatch();
//        FonusCombinedSyncJob::dispatch();

        $transactionIds = [
////            '9400150105798006163885',
////            '9400150105799006167927',
//            '9400150105497028574135',
//        '0004010549653258533685041800821'
        ];

        foreach ($transactionIds as $transactionId) {
            $data = [
                'requesterId' => 'Iweb',
                'accountId' => '1257538',
                'requestId' => Str::uuid(),
                'passPhrase' => 'Ag7ycm1274r2!a8hser2m',
                'transaction_id' => '431030',
                'pic_no' => $transactionId,
            ];
            $endicia = new EndicialabelService();
            $result = $endicia->cancelLabel($data);
        }
    }
}
