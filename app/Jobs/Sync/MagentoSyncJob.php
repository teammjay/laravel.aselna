<?php

namespace App\Jobs\Sync;

use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Magento\MagentoBrandDeviceSyncJob;
use App\Jobs\Magento\MagentoBrandPageSyncJob;
use App\Jobs\Magento\MagentoBrandSyncJob;
use App\Jobs\Magento\MagentoCategorySyncJob;
use App\Jobs\Magento\MagentoDeviceSyncJob;
use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MagentoSyncJob implements ShouldQueue
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
        $this->chainId = (string) Str::uuid();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Magento Combined']);

        try {
            $chain = [];
            $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {

                    if (($store->auto_generate_listings ?? 0) == 0) {
                        Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                        continue;
                    }

                    // 1. Category Sync
                    $categoryJob = new MagentoCategorySyncJob($store->id);
                    $categoryJob->setChainId($this->chainId);
                    $chain[] = $categoryJob;

                    // 2. Brand Sync
//                    $brandJob = new MagentoBrandSyncJob($store->id);
//                    $brandJob->setChainId($this->chainId);
//                    $chain[] = $brandJob;
//
//                    // 3. Device Sync
//                    $deviceJob = new MagentoDeviceSyncJob($store->id);
//                    $deviceJob->setChainId($this->chainId);
//                    $chain[] = $deviceJob;
//
//                    // 4. Brand-Device Relation Sync
//                    $brandDeviceJob = new MagentoBrandDeviceSyncJob($store->id);
//                    $brandDeviceJob->setChainId($this->chainId);
//                    $chain[] = $brandDeviceJob;

                    // 5. Magento Brand Page Sync (Empty as requested)
//                    $pageJob = new Completio();
                    // $pageJob->setChainId($this->chainId); // TrackableJob?
//                    $chain[] = $pageJob;
                }

                if (!empty($chain)) {
                    Bus::chain($chain)->dispatch();
                }
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Magento Combined Sync Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }
}
