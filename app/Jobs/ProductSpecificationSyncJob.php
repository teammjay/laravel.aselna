<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationValue;
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

    public $timeout = 1200;

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
            if ($storeType->code == 'temu' || $storeType->code == 'amazon') {
                continue;
            }
            $value = $productSpecification->value_;

            if ($productSpecificationUnique = ProductSpecifcationUniqueInfo::where('field_name', $productSpecification->name)->first()) {
                $newValue = ProductSpecificationValue::where('product_specification_id', $productSpecificationUnique->id)
                    ->where('field_value', $value)
                    ->first();
                if ($newValue) {
                    $value = $newValue->id;
                } else {
                    $newValue = ProductSpecificationValue::create([
                        'product_specification_id' => $productSpecificationUnique->id,
                        'field_value' => $value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $value = $newValue->id;
                }
            }


            ProductSpecification::updateOrCreate([
                'name' => $productSpecification->name,
                'product_id' => $productSpecification->product_id,
                'store_type_id' => $storeType->id
            ], [
                'name' => $productSpecification->name,
                'value' => $value,
                'product_id' => $productSpecification->product_id,
                'store_type_id' => $storeType->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
