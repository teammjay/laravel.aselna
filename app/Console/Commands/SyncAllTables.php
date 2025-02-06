<?php

namespace App\Console\Commands;

use App\Jobs\BrandSyncJob;
use App\Jobs\CarrierSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DataPatch;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DeviceRatingSyncJob;
use App\Jobs\DeviceSeriesSyncJob;
use App\Jobs\DevicesSyncJob;
use App\Jobs\DeviceTypesSyncJob;
use App\Jobs\FeedGenerationJob;
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
use App\Jobs\ProductSearchTermSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\ProductTypeKeywordSyncJob;
use App\Jobs\ShippingCarrierJob;
use App\Jobs\StoreSyncJob;
use App\Jobs\StoreTypeListingSyncJob;
use App\Jobs\StoreTypeSyncJob;
use App\Jobs\UPCSyncJob;
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
            FileSystemSyncJob::dispatch(),
            StoreTypeSyncJob::dispatch(),
            StoreSyncJob::dispatch(),
            ManufacturerSyncJob::dispatch(),
            DeviceTypesSyncJob::dispatch(),
            DeviceRatingSyncJob::dispatch(),
            BrandSyncJob::dispatch(),
            DeviceSeriesSyncJob::dispatch(),
            DevicesSyncJob::dispatch(),
            CategorySyncJob::dispatch(),
            ProductConditionJob::dispatch(),
            CarrierSyncJob::dispatch(),
            ShippingCarrierJob::dispatch(),
            InterfaceSyncJob::dispatch(),
            DeviceInterfaces::dispatch(),
            ProductColorSync::dispatch(),
            ProductSearchTermSyncJob::dispatch(),
            GenerationTypeAndTemplateSync::dispatch(),
            ProductSyncJob::dispatch(),
            ProductChannelDetailSyncJob::dispatch(),
            ProductInterfaces::dispatch(),
            ListingSyncJob::dispatch(),
            ListingItemSyncJob::dispatch(),
            ListingItemDataSyncJob::dispatch(),
            ListingItemLinkSyncJob::dispatch(),
            ListingItemProductImageSyncJob::dispatch(),
            StoreTypeListingSyncJob::dispatch(),
            ListingFeedSyncJob::dispatch(),
            UPCSyncJob::dispatch(),
            LogisticCentreSyncJob::dispatch(),
            ProductImagesSyncJob::dispatch(),
            ProductAlternativeImagesSyncJob::dispatch(),
            ProductSearchTermSyncJob::dispatch(),
            ProductTypeKeywordSyncJob::dispatch()
        ]);
//        DataPatch::dispatch();
//        FeedGenerationJob::dispatch('70ce4255-9a11-415d-8edf-c6f4112da17d');
//        ListingItemImageCreate::dispatch('31d15228-8b63-4409-8e38-b95a1b2e83fc');

    }
}
