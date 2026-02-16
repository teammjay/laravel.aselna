<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ProductSpecifcationUniqueInfo;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationValue;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductSpecificationsSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

    private $storetypes;

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
        $this->updateTrackingRecord(['tags' => 'Fonus Product Specifications']);

        try {

            $this->storetypes = StoreType::whereNotIn('code',['amazon','temu'])->pluck('id')->toArray();
            Log::info('StoreTypes to sync specifications: ' . implode(', ', $this->storetypes));

            DB::connection('fonus_db')->table('tbl_product_features')
                ->orderBy('product_id')
                ->limit(10000)
                ->chunk(10000, function ($features) {
                    $grouped = [];
                    foreach ($features as $feature) {
                        $grouped[$feature->product_id][] = $feature;
                    }
                    foreach ($grouped as $productId => $productFeatures) {
                        $this->syncProductFeatures($productId, $productFeatures);
                    }
                });

        } catch (\Throwable $e) {

            CriticalErrorService::report('Fonus Product Specifications Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncProductFeatures(mixed $productId, array $features)
    {
        $aselnaProduct = Catalog::where('product_code', $productId)->first();
        if (!$aselnaProduct) {
            return;
        }
        foreach ($features as $feature) {
            $categoryValue =  DB::connection('fonus_db')->table('tbl_category_feature_values')
                ->where('category_feature_value_id', $feature->category_feature_value_id)->first();
            if (!isset($categoryValue) || !isset($categoryValue->feature_id)) continue;

            $feature = DB::connection('fonus_db')->table('tbl_features')
                ->where('feature_id', $categoryValue->feature_id)->first();

            $value = $categoryValue->value;
            Log::info('Product Specification :: ' . $feature->feature . ' with ' . $value . ' for Product ID: ' . $productId);

            $uniqueSpecification = ProductSpecifcationUniqueInfo::where('field_name', $feature->feature)->first();
            if (isset($uniqueSpecification)) {
                $specValue = ProductSpecificationValue::where('product_specification_id', $uniqueSpecification->id)
                    ->where('field_value', $value)
                    ->first();
                if ($specValue) {
                    $value = $specValue->id;
                }
            }

            foreach ($this->storetypes as $storetype) {
                ProductSpecification::updateOrCreate(
                    [
                        'name' => $feature->feature,
                        'product_id' => $aselnaProduct->product_id,
                        'store_type_id' => $storetype
                    ],
                    [
                        'name' => $feature->feature,
                        'product_id' => $aselnaProduct->product_id,
                        'store_type_id' => $storetype,
                        'value' => $value,
                    ]
                );
            }
        }
    }
}
