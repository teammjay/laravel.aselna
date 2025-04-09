<?php

namespace App\Console\Commands;

use App\Jobs\AmazonListingItemSyncJob;
use App\Jobs\AmazonProductTypeSyncJob;
use App\Jobs\BrandSyncJob;
use App\Jobs\CarrierSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DataPatch;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DeviceRatingSyncJob;
use App\Jobs\DeviceSeriesSyncJob;
use App\Jobs\DevicesLinkSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\DeviceTypesSyncJob;
use App\Jobs\FeedGenerationJob;
use App\Jobs\FetchAmazonOrdersJob;
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
use App\Jobs\ManufacturerSyncJob;
use App\Jobs\ProductAlternativeImagesSyncJob;
use App\Jobs\ProductChannelDetailSyncJob;
use App\Jobs\ProductColorSync;
use App\Jobs\ProductConditionJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductMaterialSyncJob;
use App\Jobs\ProductSearchTermSyncJob;
use App\Jobs\ProductSizeSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\ProductTagSyncJob;
use App\Jobs\ProductTypeKeywordSyncJob;
use App\Jobs\ShippingCarrierJob;
use App\Jobs\ShopifyCollectionDeviceSyncJob;
use App\Jobs\StoreCategorySyncJob;
use App\Jobs\StorePagesJobSync;
use App\Jobs\StoreProductSyncJob;
use App\Jobs\StoreSyncJob;
use App\Jobs\StoreTypeListingSyncJob;
use App\Jobs\StoreTypeSyncJob;
use App\Jobs\UPCSyncJob;
use App\Models\ListingTemplate;
use App\Models\ProductMaterial;
use App\Models\ProductSize;
use App\Models\ProductType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

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
        // Bus::chain([
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
        // ])->dispatch();
//        Bus::chain([
//            ListingSyncJob::dispatch(),
//            ListingItemSyncJob::dispatch(),
//            ListingItemDataSyncJob::dispatch(),
//            ListingItemLinkSyncJob::dispatch(),
//            ListingItemProductImageSyncJob::dispatch(),
//            StoreTypeListingSyncJob::dispatch(),
//            ListingFeedSyncJob::dispatch(),
//            UPCSyncJob::dispatch(),
//            LogisticCentreSyncJob::dispatch(),
//            ProductImagesSyncJob::dispatch();
//            ProductAlternativeImagesSyncJob::dispatch();
//        ]);
//        DataPatch::dispatch();
//           FeedGenerationJob::dispatch('2d075d34-03b5-436e-b5b1-2a45a508beb3');
        //    FeedGenerationJob::dispatch('4e7f02b9-3530-4e87-ad72-8cf5881ebe66');
        ListingItemImageCreate::dispatch('45a0b556-f1f8-445f-a83f-498ee612e680');
//       FeedGenerationJob::dispatch('80415096-6ce4-4f55-b862-f1a3d3ba4662');
        //    ListingItemImageCreate::dispatch('4dcf1d3f-b723-4b31-a075-3702c0746b10');
//        FetchAmazonOrdersJob::dispatch();
//        StoreCategorySyncJob::dispatch('94428796-cdf9-dc2b-a1e7-aa4100066fd9');
//        AmazonListingItemSyncJob::dispatch();
//        AmazonProductTypeSyncJob::dispatch();

    }
}
