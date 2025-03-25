<?php

namespace App\Jobs;

use App\Models\ProductSpecification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductSpecificationSyncJob implements ShouldQueue
{
    use Queueable;

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
        DB::connection('easyesale_db')->table('product_specification')->orderBy('id')
            ->chunk(1000, function ($productSpecifications) {
                foreach ($productSpecifications as $productSpecification) {
                    $this->createProductSpecification($productSpecification);
                }
            });
    }

    private function createProductSpecification(mixed $productSpecification)
    {
        ProductSpecification::updateOrCreate([
            'id' => $productSpecification->id,
        ], [
            'id' => $productSpecification->id,
            'name' => $productSpecification->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
