<?php

namespace App\Jobs\Sync;


use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Fonus\BrandSyncJob;
use App\Jobs\Fonus\CarrierSyncJob;
use App\Jobs\Fonus\CategorySyncJob;
use App\Jobs\Fonus\ChainCompletionJob;
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
use App\Jobs\Fonus\ProductSpecificationsSyncJob;
use App\Jobs\Fonus\ProductSyncJob;
use App\Jobs\Fonus\ProductTitleKeyword;
use App\Jobs\ProductAlternativeImagesSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Models\JobStatus;
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
        // Keep parent job as "processing" - NOT completed yet
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Combined DB Sync'
        ]);

        try {
            $chain = [
                new ProductcolorSyncJob(), // DONE
                new ManufacturerSyncJob(), // DONE
                new CarrierSyncJob(), // DONE
                new ProductSyncJob(), // Partial Done
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
                new PopularDevicesSyncJob(), // DONE
                new ProductImagesSyncJob(), // DONE
                new ProductAlternativeImagesSyncJob(), // DONE
                new ProductSpecificationsSyncJob(),
            ];

            // Add the completion job at the end of the chain to mark parent as completed
            $chain[] = new ChainCompletionJob($this->jobId);

            // Create pending record for the completion job
            $this->createPendingRecordsForChain($chain, $this->jobId);

            // Store parent job ID for the catch callback
            $parentJobId = $this->jobId;

            // Dispatch chain with catch callback to handle failures
            Bus::chain($chain)
                ->catch(function (\Throwable $e) use ($parentJobId) {
                    // Mark parent as failed if chain fails
                    JobStatus::where('job_id', $parentJobId)
                        ->update(['status' => 'failed', 'output' => $e->getMessage(), 'finished_at' => now()]);

                    Log::error('Fonus Combined Sync Chain Failed: ' . $e->getMessage());

                    CriticalErrorService::report('Fonus Combined DB Sync Chain Failed', [
                        'marketplace' => 'Shopify',
                        'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                        'method' => __METHOD__
                    ]);
                })
                ->dispatch();
        } catch (\Throwable $e) {
            Log::error('Error: ' . $e->getMessage());

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Combined DB Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }
}
