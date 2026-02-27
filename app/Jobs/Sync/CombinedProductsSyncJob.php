<?php

namespace App\Jobs\Sync;

use App\Jobs\BrandSyncJob;
use App\Jobs\CategorySyncJob;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\DeviceCarrierSyncJob;
use App\Jobs\DeviceInterfaces;
use App\Jobs\DevicesSyncJob;
use App\Jobs\Fonus\ChainCompletionJob;
use App\Jobs\InterfaceSyncJob;
use App\Jobs\ProductImagesSyncJob;
use App\Jobs\ProductInterfaces;
use App\Jobs\ProductKeyFeaturesSyncJob;
use App\Jobs\ProductSpecificationSyncJob;
use App\Jobs\ProductSyncJob;
use App\Jobs\SubmitShopifyProductsJob;
use App\Models\JobStatus;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CombinedProductsSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Comined Products Sync']);

        try {
            $chain = [
                new ProductSyncJob(),
                new ProductKeyFeaturesSyncJob(),
                new InterfaceSyncJob(),
                new CategorySyncJob(),
                new BrandSyncJob(),
                new ProductInterfaces(),
                new ProductSpecificationSyncJob(),
                new ProductImagesSyncJob()
            ];

            $chain[] = new ChainCompletionJob($this->jobId);

            // Create pending record for the completion job
            $this->createPendingRecordsForChain($chain, $this->jobId);

            // Store parent job ID for the catch callback
            $parentJobId = $this->jobId;

            Bus::chain($chain)
                ->catch(function (\Throwable $e) use ($parentJobId) {
                    // Mark parent as failed if chain fails
                    JobStatus::where('job_id', $parentJobId)
                        ->update(['status' => 'failed', 'output' => $e->getMessage(), 'finished_at' => now()]);
                })
                ->dispatch();
        } catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
            Log::error('Error: ' . $e->getMessage());

            CriticalErrorService::report('Comined Products Sync Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }
}
