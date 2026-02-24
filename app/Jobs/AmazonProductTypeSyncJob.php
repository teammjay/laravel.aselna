<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\AmazonAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AmazonProductTypeSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    private $amazonAuthService;

    public $timeout = 30000;

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
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Amazon ProductType']);

            $this->amazonAuthService = new AmazonAuthService();
            StoreTypeCategory::whereIn('store_type_id', function ($subQuery) {
                return $subQuery->select('id')->from('store_type')->where('code', 'amazon');
            })
                ->whereNull('marketplace_code')
                ->orderBy('id')->chunk(1000, function ($storeProductTypes) {
                    foreach ($storeProductTypes as $storeProductType) {
                        $this->syncAmazonProductType($storeProductType);
                        sleep(1);
                    }
                });

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

            CriticalErrorService::report('Amazon Product Type Sync Job Failed', [
                'marketplace' => 'Amazon',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    private function syncAmazonProductType(mixed $storeProductType)
    {

        Log::info('Syncing Amazon product type' . json_encode($storeProductType));
        $result = $this->amazonAuthService->getProductTypes($storeProductType['name']);
        if (isset($result['productTypes'])) {
            $productTypes = $result['productTypes'];
            foreach ($productTypes as $productType) {
                $storeProductType->update([
                    'marketplace_code' => $productType['name'],
                ]);
                break;
            }
        } else {
            Log::error('Error syncing Amazon product type: ' . json_encode($result));
        }
    }
}
