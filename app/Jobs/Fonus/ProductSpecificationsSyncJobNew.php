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

class ProductSpecificationsSyncJobNew implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

    private array $storetypes;
    private array $features;
    private array $categoryFeatures;
    private array $featureMap;
    private array $catalogMap;
    private array $uniqueSpecifications;
    private array $existingSpecValues;
    private array $existingProductSpecs;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Fonus Product Specifications']);

        try {
            $this->loadInitialData();
            $this->processFeatures();
            $this->cleanupOrphanedSpecifications();
        } catch (\Throwable $e) {
            CriticalErrorService::report('Fonus Product Specifications Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function loadInitialData(): void
    {
        $this->features = DB::connection('fonus_db')
            ->table('tbl_features')
            ->pluck('feature', 'feature_id')
            ->toArray();

        $this->categoryFeatures = DB::connection('fonus_db')
            ->table('tbl_category_feature_values')
            ->select('category_feature_value_id', 'feature_id', 'value')
            ->get()
            ->keyBy('category_feature_value_id')
            ->toArray();

        $this->storetypes = StoreType::whereNotIn('code', ['amazon', 'temu'])
            ->pluck('id')
            ->toArray();

        // Pre-load catalog mapping (product_code => product_id)
        $this->catalogMap = Catalog::pluck('product_id', 'product_code')->toArray();

        // Pre-load unique specifications
        $this->uniqueSpecifications = ProductSpecifcationUniqueInfo::pluck('id', 'field_name')->toArray();

        // Pre-load existing spec values
        $this->existingSpecValues = ProductSpecificationValue::select('id', 'product_specification_id', 'field_value')
            ->get()
            ->groupBy('product_specification_id')
            ->map(fn($items) => $items->pluck('id', 'field_value')->toArray())
            ->toArray();

        $this->featureMap = [];

        Log::info('StoreTypes to sync specifications: ' . implode(', ', $this->storetypes));
    }

    private function processFeatures(): void
    {
        DB::connection('fonus_db')
            ->table('tbl_product_features')
            ->orderBy('product_id')
            ->chunk(2000, function ($features) {
                $grouped = [];
                $productCodes = [];

                foreach ($features as $feature) {
                    $grouped[$feature->product_id][] = $feature;
                    $productCodes[] = $feature->product_id;

                    if (
                        isset($feature->category_feature_value_id) &&
                        isset($this->categoryFeatures[$feature->category_feature_value_id])
                    ) {
                        $featureId = $this->categoryFeatures[$feature->category_feature_value_id]->feature_id;
                        if (isset($this->features[$featureId])) {
                            $this->featureMap[$feature->product_id][] = $this->features[$featureId];
                        }
                    }
                }

                // Pre-load existing specifications for this batch
                $aselnaProductIds = array_filter(
                    array_map(fn($code) => $this->catalogMap[$code] ?? null, array_unique($productCodes))
                );

                $this->existingProductSpecs = ProductSpecification::whereIn('product_id', $aselnaProductIds)
                    ->whereIn('store_type_id', $this->storetypes)
                    ->get()
                    ->groupBy(fn($spec) => "{$spec->product_id}_{$spec->store_type_id}_{$spec->name}")
                    ->map(fn($items) => $items->first())
                    ->toArray();

                $this->syncProductFeaturesBatch($grouped);
            });
    }

    private function syncProductFeaturesBatch(array $grouped): void
    {
        $inserts = [];
        $updates = [];
        $newSpecValues = [];

        foreach ($grouped as $productId => $features) {
            $aselnaProductId = $this->catalogMap[$productId] ?? null;
            if (!$aselnaProductId) {
                continue;
            }

            foreach ($features as $feature) {
                $categoryValue = $this->categoryFeatures[$feature->category_feature_value_id] ?? null;
                if (!$categoryValue || !isset($categoryValue->feature_id)) {
                    continue;
                }

                $featureValue = $this->features[$categoryValue->feature_id] ?? null;
                if (!$featureValue) {
                    continue;
                }

                $specValueText = $categoryValue->value;
                $value = $this->resolveSpecificationValue($featureValue, $specValueText, $newSpecValues);

                foreach ($this->storetypes as $storetype) {
                    $key = "{$aselnaProductId}_{$storetype}_{$featureValue}";
                    $existing = $this->existingProductSpecs[$key] ?? null;

                    if ($existing) {
                        // Only update if value changed
                        if ($existing->value !== $value) {
                            $updates[] = [
                                'id' => $existing->id,
                                'value' => $value,
                                'updated_at' => now(),
                            ];
                        }
                    } else {
                        $inserts[] = [
                            'name' => $featureValue,
                            'product_id' => $aselnaProductId,
                            'store_type_id' => $storetype,
                            'value' => $value,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }

        // Bulk insert new spec values
        if (!empty($newSpecValues)) {
            ProductSpecificationValue::insert($newSpecValues);
            // Refresh the cache
            $this->refreshSpecValuesCache(array_column($newSpecValues, 'product_specification_id'));
        }

        // Bulk insert new specifications
        if (!empty($inserts)) {
            foreach (array_chunk($inserts, 500) as $chunk) {
                ProductSpecification::insert($chunk);
            }
            Log::info('Inserted ' . count($inserts) . ' new specifications');
        }

        // Bulk update changed specifications
        if (!empty($updates)) {
            foreach ($updates as $update) {
                ProductSpecification::where('id', $update['id'])->update([
                    'value' => $update['value'],
                    'updated_at' => $update['updated_at'],
                ]);
            }
            Log::info('Updated ' . count($updates) . ' specifications');
        }
    }

    private function resolveSpecificationValue(string $featureValue, string $specValueText, array &$newSpecValues): mixed
    {
        $uniqueSpecId = $this->uniqueSpecifications[$featureValue] ?? null;

        if (!$uniqueSpecId) {
            return $specValueText;
        }

        // Check cached values first
        if (isset($this->existingSpecValues[$uniqueSpecId][$specValueText])) {
            return $this->existingSpecValues[$uniqueSpecId][$specValueText];
        }

        // Queue for bulk insert
        $newSpecValues[] = [
            'product_specification_id' => $uniqueSpecId,
            'field_value' => $specValueText,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return $specValueText; // Will be resolved after insert
    }

    private function refreshSpecValuesCache(array $specIds): void
    {
        $newValues = ProductSpecificationValue::whereIn('product_specification_id', array_unique($specIds))
            ->get()
            ->groupBy('product_specification_id')
            ->map(fn($items) => $items->pluck('id', 'field_value')->toArray())
            ->toArray();

        $this->existingSpecValues = array_merge($this->existingSpecValues, $newValues);
    }

    private function cleanupOrphanedSpecifications(): void
    {
        $deletionIds = [];

        ProductSpecification::where(function ($query) {
                $query->whereIn('store_type_id', $this->storetypes)
                    ->orWhereNull('store_type_id');
            })
            ->select('id', 'name', 'product_id')
            ->with('product:id,product_code')
            ->chunkById(2000, function ($specifications) use (&$deletionIds) {
                foreach ($specifications as $specification) {
                    $productCode = $specification->product->product_code ?? null;
                    if (!$productCode) {
                        continue;
                    }

                    if (!isset($this->featureMap[$productCode]) ||
                        !in_array($specification->name, $this->featureMap[$productCode])) {
                        $deletionIds[] = $specification->id;
                    }
                }
            });

        if (!empty($deletionIds)) {
            foreach (array_chunk($deletionIds, 1000) as $chunk) {
                ProductSpecification::whereIn('id', $chunk)->delete();
            }
            Log::info('Deleted ' . count($deletionIds) . ' orphaned specifications');
        }
    }
}
