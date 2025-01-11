<?php

namespace App\Console\Commands;

use App\Jobs\BrandSyncJob;
use App\Jobs\CarrierSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DevicesSyncJob;
use App\Jobs\DeviceTypesSyncJob;
use App\Jobs\GenerationTypeAndTemplateSync;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ListingFeedSyncJob;
use App\Jobs\ListingItemDataSyncJob;
use App\Jobs\ListingItemLinkSyncJob;
use App\Jobs\ListingItemSyncJob;
use App\Jobs\ListingSyncJob;
use App\Jobs\LogisticCentreSyncJob;
use App\Jobs\ManufacturerSyncJob;
use App\Jobs\ProductConditionJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductSyncJob;
use App\Jobs\ShippingCarrierJob;
use App\Jobs\StoreSyncJob;
use App\Jobs\StoreTypeListingSyncJob;
use App\Jobs\StoreTypeSyncJob;
use App\Jobs\UPCSyncJob;
use Illuminate\Console\Command;

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
//        StoreTypeSyncJob::dispatch();
//        StoreSyncJob::dispatch();
//        ManufacturerSyncJob::dispatch();
//        DeviceTypesSyncJob::dispatch();
//        BrandSyncJob::dispatch();
//        DevicesSyncJob::dispatch();
//        CategorySyncJob::dispatch();
//        ProductConditionJob::dispatch();
//        CarrierSyncJob::dispatch();
//        ShippingCarrierJob::dispatch();
//        InterfaceSyncJob::dispatch();
//        DeviceInterfaces::dispatch();
//        GenerationTypeAndTemplateSync::dispatch();
//        ProductSyncJob::dispatch();
//        ProductInterfaces::dispatch();
//        ListingSyncJob::dispatch();
//        ListingItemSyncJob::dispatch();
//        ListingItemDataSyncJob::dispatch();
//        ListingItemLinkSyncJob::dispatch();
//        StoreTypeListingSyncJob::dispatch();
//        UPCSyncJob::dispatch();
//        LogisticCentreSyncJob::dispatch();
//        ListingFeedSyncJob::dispatch();
        ProductImagesSyncJob::dispatch();
    }
}
