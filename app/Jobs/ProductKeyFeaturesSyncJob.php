<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductKeyFeaturesSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 60000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->updateTrackingRecord(['tags' => 'Product Key Features']);

        try {
            DB::connection('easyesale_db')->table('product_key_feature')->orderBy('id')->chunk(1000, function ($productKeyFeatures) {
                foreach ($productKeyFeatures as $productKeyFeature) {
                    \App\Models\ProductKeyFeatures::updateOrCreate([
                        'id' => $productKeyFeature->id
                    ], [
                        'id' => $productKeyFeature->id,
                        'product_id' => $productKeyFeature->product_id,
                        'value' => $productKeyFeature->value,
                        'sort_order' => $productKeyFeature->sort_order,
                    ]);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Key Features Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
