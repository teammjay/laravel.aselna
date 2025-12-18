<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductKeyFeaturesSyncJob implements ShouldQueue
{
    use Queueable;

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
    }
}
