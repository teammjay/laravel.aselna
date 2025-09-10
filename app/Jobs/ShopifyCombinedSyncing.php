<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ShopifyCombinedSyncing implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ProductSyncJob::withChain([
            new InterfaceSyncJob(),
            new DeviceSeriesSyncJob(),
            new CategorySyncJob(),
            new BrandSyncJob(),
            new DevicesSyncJob(),
            new DeviceCarrierSyncJob(),
            new DeviceInterfaces(),
            new ProductInterfaces(),
            new ProductImagesSyncJob(),
            new SubmitShopifyProductsJob()
        ])->dispatch();
    }
}
