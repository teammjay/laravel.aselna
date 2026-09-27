<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Models\Brand;
use App\Models\Device;
use App\Models\Store;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Concerns\ScopesToStores;
use App\Jobs\Contracts\StoreScopedJob;

class MagnetoAttributesSyncJob implements ShouldQueue, StoreScopedJob
{
    use Queueable, ScopesToStores, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public static function eligibleStoreTypes(): array
    {
        return ['magento'];
    }

    /**
     * The store query below does not filter on store_status.
     */
    public static function onlyActiveStores(): bool
    {
        return false;
    }

    /**
     * Create a new job instance.
     */
    public function __construct(string|array|null $storeIds = null)
    {
        $this->initStoreScope($storeIds);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Magento Attributes']);

        try {
            /* get active store jobs*/
            $store_ids = $this->resolveStoreIds();
            /* get active store jobs*/

            $storeType = StoreType::where('name', 'magento')->first();
            $query = Store::where('store_type_id', $storeType->id);

            if (!empty($store_ids)) {
                $query->whereIn('id', $store_ids);
            }

            $stores = $query->get();

            foreach ($stores as $store) {
                $this->trackCurrentStore($store, count($stores));
                $this->SyncAttributesIfNeeded($store);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Magento Attributes Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

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
