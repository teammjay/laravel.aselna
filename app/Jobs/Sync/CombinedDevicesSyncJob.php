<?php

namespace App\Jobs\Sync;

use App\Jobs\BrandSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DevicesSyncJob;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductKeyFeaturesSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CombinedDevicesSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Comined Devices Sync']);

        try {
            $chain = [
                new InterfaceSyncJob(),
                new BrandSyncJob(),
                new DevicesSyncJob(),
                new DeviceCarrierSyncJob(),
                new DeviceInterfaces(),
            ];

            foreach ($chain as $job) {
                if (method_exists($job, 'setChainId')) {
                    $job->setChainId($this->chainId);
                }
            }

            Bus::chain($chain)->dispatch();
        } catch (\Throwable $e) {
            Log::error('Error: ' . $e->getMessage());

            CriticalErrorService::report('Comined Devices Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }

    }
}
