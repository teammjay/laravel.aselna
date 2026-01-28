<?php

namespace App\Jobs\Sync;


use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Fonus\BrandSyncJob;
use App\Jobs\Fonus\ProductKeyFeaturesSyncJob;
use App\Jobs\Fonus\ProductSyncJob;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class FonusCombinedSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Fonus Combined DB Sync']);

        try {
            $chain = [
//                new ProductSyncJob(), // Partial Done
                new ProductKeyFeaturesSyncJob(),
//                new InterfaceSyncJob(),
//            new DeviceSeriesSyncJob(),
//                new CategorySyncJob(),
//                new BrandSyncJob(), // DONE
//                new DevicesSyncJob(),
//                new DeviceCarrierSyncJob(),
//                new DeviceInterfaces(),
//                new ProductInterfaces(),
//                new ProductSpecificationSyncJob(),
//                new ProductImagesSyncJob(),
            ];

            foreach ($chain as $job) {
                if (method_exists($job, 'setChainId')) {
                    $job->setChainId($this->chainId);
                }
            }

            Bus::chain($chain)->dispatch();
        } catch (\Throwable $e) {
            Log::error('Error: ' . $e->getMessage());

            CriticalErrorService::report('Fonus Combined DB Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }
}
