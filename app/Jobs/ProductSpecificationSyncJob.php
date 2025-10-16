<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductSpecification;
use App\Models\StoreType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;
use Illuminate\Support\Facades\Log;

class ProductSpecificationSyncJob implements ShouldQueue
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
        Log::info('Product Specification Sync Job started.');
        DB::connection('easyesale_db')
            ->table('product_specification')
            ->orderBy('id')
            ->chunk(1000, function ($productSpecifications) {
                foreach ($productSpecifications as $productSpecification) {
                    $this->createProductSpecification($productSpecification);
                }
            });
        Log::info('Product Specification Sync Job completed.');
    }

    private function createProductSpecification(mixed $productSpecification)
    {
        if (empty($productSpecification->product_id) || Catalog::where('product_id', $productSpecification->product_id)->doesntExist()) {
            return;
        }
        $storeTypes = StoreType::all();
        foreach ($storeTypes as $storeType) {
            ProductSpecification::updateOrCreate([
                'name' => $productSpecification->name,
                'product_id' => $productSpecification->product_id,
                'store_type_id' => $storeType->id
            ], [
                'name' => $productSpecification->name,
                'value' => $productSpecification->value_,
                'product_id' => $productSpecification->product_id,
                'store_type_id' => $storeType->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
