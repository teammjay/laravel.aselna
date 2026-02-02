<?php

namespace App\Jobs\Sync;


use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Fonus\BrandSyncJob;
use App\Jobs\Fonus\CarrierSyncJob;
use App\Jobs\Fonus\CategorySyncJob;
use App\Jobs\Fonus\DeviceCarrierSyncJob;
use App\Jobs\Fonus\DeviceChannelSyncJob;
use App\Jobs\Fonus\DevicesInterfacesSyncJob;
use App\Jobs\Fonus\DevicesSyncJob;
use App\Jobs\Fonus\DeviceTypeSyncJob;
use App\Jobs\Fonus\InterfacesSyncJob;
use App\Jobs\Fonus\ManufacturerSyncJob;
use App\Jobs\Fonus\PopularDevicesSyncJob;
use App\Jobs\Fonus\ProductChannelSyncJob;
use App\Jobs\Fonus\ProductcolorSyncJob;
use App\Jobs\Fonus\ProductInterfacesSyncJob;
use App\Jobs\Fonus\ProductKeyFeaturesSyncJob;
use App\Jobs\Fonus\ProductSyncJob;
use App\Jobs\Fonus\ProductTitleKeyword;
use App\Jobs\ProductAlternativeImagesSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Models\Carrier;
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
                new ProductcolorSyncJob(), // DONE
                new ManufacturerSyncJob(), // DONE
                new CarrierSyncJob(), // DONE
                new ProductSyncJob(), // Partial Done
                new ProductKeyFeaturesSyncJob(), // DONE
                new InterfacesSyncJob(), // DONE
                new CategorySyncJob(), // DONE
                new BrandSyncJob(), // DONE
                new DeviceTypeSyncJob(),// DONE
                new DevicesSyncJob(), // DONE
                new DeviceCarrierSyncJob(), // DONE
                new DevicesInterfacesSyncJob(),// DONE
                new ProductInterfacesSyncJob(), // DONE
                new ProductTitleKeyword(), // DONE
                new ProductChannelSyncJob(), // DONE
//                new DeviceChannelSyncJob(),// DONE
                new ProductImagesSyncJob(), // DONE
                new ProductAlternativeImagesSyncJob(), // DONE
                new PopularDevicesSyncJob(), // DONE
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
