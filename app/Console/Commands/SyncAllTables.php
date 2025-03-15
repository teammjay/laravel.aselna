<?php

namespace App\Console\Commands;

use App\Jobs\BrandSyncJob;
use App\Jobs\CarrierSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DataPatch;
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
use App\Jobs\ProductSyncJob;
use App\Jobs\ProductTagSyncJob;
use App\Jobs\ProductTypeKeywordSyncJob;
use App\Jobs\ShippingCarrierJob;
use App\Jobs\ShopifyCollectionDeviceSyncJob;
use App\Jobs\StoreProductSyncJob;
use App\Jobs\StoreSyncJob;
use App\Jobs\StoreTypeListingSyncJob;
use App\Jobs\StoreTypeSyncJob;
use App\Jobs\UPCSyncJob;
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
        Bus::chain([
//            new FileSystemSyncJob(),
//            new StoreTypeSyncJob(),
//            new StoreSyncJob(),
//            new ManufacturerSyncJob(),
//            new DeviceTypesSyncJob(),
//            new DeviceRatingSyncJob(),
//            new DeviceSeriesSyncJob(),
//            new BrandSyncJob(),
//            new CategorySyncJob(),
//            new InterfaceSyncJob(),
//            new DevicesSyncJob(),
//            new DeviceInterfaces(),
//            new ProductConditionJob(),
//            new CarrierSyncJob(),
//            new ShippingCarrierJob(),
//            new ProductColorSync(),
//            new ProductSizeSyncJob(),
//            new ProductMaterialSyncJob(),
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
//            new ProductImagesSyncJob(),
//            new ProductAlternativeImagesSyncJob(),
        ])->dispatch();
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
//            ProductImagesSyncJob::dispatch(),
//            ProductAlternativeImagesSyncJob::dispatch(),
//        ]);
//        DataPatch::dispatch();
//        FeedGenerationJob::dispatch('70ce4255-9a11-415d-8edf-c6f4112da17d');
//        ListingItemImageCreate::dispatch('aae136d8-6f03-4106-904a-8fda6518b031');
//        FetchAmazonOrdersJob::dispatch();
//        ShopifyCollectionDeviceSyncJob::dispatch('6c7009ce-eac1-a995-a986-27d5df0103ac');

    }
}
