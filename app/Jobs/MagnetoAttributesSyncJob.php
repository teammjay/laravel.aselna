<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Device;
use App\Models\Store;
use App\Models\StoreType;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class MagnetoAttributesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Magento Attributes']);

        $storeType = StoreType::where('name', 'magento')->first();
        $stores = Store::where('store_type_id', $storeType->id)->get();

        foreach ($stores as $store) {
            $this->SyncAttributesIfNeeded($store);
        }
    }

    private function SyncAttributesIfNeeded($store)
    {
        Log::info('Syncing attributes for store: ' . $store->name);
        $magentoService = new MagentoService();

//        $magentoService->syncAttributes($store);

        $brands = Brand::where('status','active')->select('id','name')->get();
        $magentoService->syncAttributeData($brands,'brands', $store);

        $devices = Device::where('status', 'active')->select('id', 'name')->get();
        $magentoService->syncAttributeData($devices,'devices', $store);
    }
}
