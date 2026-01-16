<?php

namespace App\Jobs;

use App\Models\ProductMaterial;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductMaterialSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Product Materials']);

        try {
            DB::connection('easyesale_db')->table('product_material')->orderBy('id')
                ->chunk(1000, function ($productMaterials) {
                    foreach ($productMaterials as $productMaterial) {
                        $this->createProductMaterial($productMaterial);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Materials Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductMaterial($productMaterial)
    {

        ProductMaterial::updateOrCreate([
            'id' => $productMaterial->id,
        ], [
            'id' => $productMaterial->id,
            'name' => $productMaterial->name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
