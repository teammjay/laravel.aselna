<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductSpecification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

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
        $this->updateTrackingRecord(['tags' => 'Product Specification']);

        DB::connection('easyesale_db')->table('product_specification')->orderBy('id')
            ->chunk(1000, function ($productSpecifications) {
                foreach ($productSpecifications as $productSpecification) {
                    $this->createProductSpecification($productSpecification);
                }
            });
    }

    private function createProductSpecification(mixed $productSpecification)
    {
        if (empty($productSpecification->product_id) || Catalog::where('product_id', $productSpecification->product_id)->doesntExist()) {
            return;
        }
        ProductSpecification::updateOrCreate([
            'id' => $productSpecification->id,
        ], [
            'id' => $productSpecification->id,
            'name' => $productSpecification->name,
            'value' => $productSpecification->value_,
            'product_id' => $productSpecification->product_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
