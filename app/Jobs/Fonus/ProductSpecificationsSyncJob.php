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
     * @var mixed[]
     */
    private array $features;
    /**
     * @var mixed[]
     */
    private array $categoryFeatures;
    private array $featureMap;
    private $catalogMap;
    private $uniqueSpecifications;

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
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Product Specifications'
        ]);

        try {

            $this->features =  DB::connection('fonus_db')->table('tbl_features')->pluck('feature','feature_id')->toArray();
            $this->categoryFeatures = DB::connection('fonus_db')->table('tbl_category_feature_values')
                ->pluck('feature_id','category_feature_value_id')->toArray();

            $this->storetypes = StoreType::whereNotIn('code',['amazon','temu'])->pluck('id')->toArray();
            Log::info('StoreTypes to sync specifications: ' . implode(', ', $this->storetypes));

            $this->catalogMap = Catalog::pluck('product_id', 'product_code')->toArray();
            $this->uniqueSpecifications = ProductSpecifcationUniqueInfo::pluck('id', 'field_name')->toArray();

            $this->featureMap = [];

            DB::connection('fonus_db')->table('tbl_product_features')
                ->orderBy('product_id')
                ->chunk(1000, function ($features) {
                    $grouped = [];
                    foreach ($features as $feature) {
                        $grouped[$feature->product_id][] = $feature;
                        if (
                            isset($feature->product_id) &&
                            isset($feature->category_feature_value_id) &&
                            isset($this->categoryFeatures[$feature->category_feature_value_id]) &&
                            isset($this->features[$this->categoryFeatures[$feature->category_feature_value_id]])
                        ) {
                            $this->featureMap[$feature->product_id][] = $this->features[$this->categoryFeatures[$feature->category_feature_value_id]];
                        }
                    }
                    foreach ($grouped as $productId => $productFeatures) {
                        $this->syncProductFeatures($productId, $productFeatures);
                    }
                });


            ProductSpecification::orderBy('product_id')
                ->where(function ($query) {
                    $query->whereIn('store_type_id', $this->storetypes)
                        ->orWhereNull('store_type_id');
                })
                ->groupBy('product_id', 'name')
                ->select('name', 'product_id')
                ->with('product')
                ->chunk(1000, function ($specifications) {
                    $this->checkAndDeleteSpecifications($specifications);
                });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Product Specifications Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function syncProductFeatures(mixed $productId, array $features)
    {
        $aselnaProductId = $this->catalogMap[$productId] ?? null;
        if (!$aselnaProductId) {
            return;
        }
        foreach ($features as $feature) {
            $categoryValue =  DB::connection('fonus_db')->table('tbl_category_feature_values')
                ->where('category_feature_value_id', $feature->category_feature_value_id)->first();
            if (!isset($categoryValue) || !isset($categoryValue->feature_id)) continue;

            $featureValue = $this->features[$categoryValue->feature_id] ?? null;
            if (!isset($feature)) continue;

            $value = $specValueText = $categoryValue->value;

            $uniqueSpecificationId = $this->uniqueSpecifications[$featureValue] ?? null;

            if (isset($uniqueSpecificationId)) {
                $specValue = ProductSpecificationValue::select('id')->where('product_specification_id', $uniqueSpecificationId)
                    ->where('field_value', $specValueText)
                    ->first();
                if ($specValue) {
                    $value = $specValue->id;
                } else {
                    $specValue = ProductSpecificationValue::create([
                        'product_specification_id' => $uniqueSpecificationId,
                        'field_value' => $specValueText,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $value = $specValue->id;
                }
            }

            foreach ($this->storetypes as $storetype) {
                ProductSpecification::updateOrCreate(
                    [
                        'name' => $featureValue,
                        'product_id' => $aselnaProductId,
                        'store_type_id' => $storetype
                    ],
                    [
                        'name' => $featureValue,
                        'product_id' => $aselnaProductId,
                        'store_type_id' => $storetype,
                        'value' => $value,
                    ]
                );
            }
        }
    }

    private function checkAndDeleteSpecifications($specifications)
    {
        $deletionIds = [];
        foreach ($specifications as $specification) {
            $productId = $specification->product->product_code;
            $featureName = $specification->name;

            if (isset($this->featureMap[$productId]) && in_array($featureName, $this->featureMap[$productId])) {
                continue; // This specification is still valid, skip deletion
            }
            $deletionIds[$specification->product_id][] = $featureName; // Mark for deletion
        }

        foreach ($deletionIds as $productId => $features) {
            Log::info("Deleting specifications for Product ID: $productId, Features: " . implode(', ', $features));
            ProductSpecification::where('product_id', $productId)
                ->whereIn('name', $features)
                ->whereIn('store_type_id', $this->storetypes)
                ->delete();
        }
    }
}
