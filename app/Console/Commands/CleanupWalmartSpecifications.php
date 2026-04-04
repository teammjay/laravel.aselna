<?php

namespace App\Console\Commands;

use App\Models\ListingRequirement;
use App\Models\ProductSpecification;
use App\Models\ProductSpecificationInfo;
use App\Models\StoreType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupWalmartSpecifications extends Command
{
    protected $signature = 'walmart:cleanup-specs';
    protected $description = 'Remove redundant flat specification keys that have been superseded by nested dot-notation keys.';

    public function handle()
    {
        $walmartStoreType = StoreType::where('code', 'walmart')->first();
        if (!$walmartStoreType) {
            $this->error('Walmart store type not found.');
            return;
        }

        $storeTypeId = $walmartStoreType->id;

        // 1. Identify all keys for Walmart
        $allKeys = ProductSpecificationInfo::where('store_type_id', $storeTypeId)
            ->pluck('field_name')
            ->toArray();

        $nestedKeys = array_filter($allKeys, fn($key) => str_contains($key, '.'));
        $flatKeys = array_filter($allKeys, fn($key) => !str_contains($key, '.'));

        $keysToRemove = [];

        foreach ($flatKeys as $flatKey) {
            // Check if any nested key starts with this flat key followed by a dot
            // Example: if 'volts.0.unit' exists, then 'volts' is redundant
            foreach ($nestedKeys as $nestedKey) {
                if (str_starts_with($nestedKey, $flatKey . '.')) {
                    $keysToRemove[] = $flatKey;
                    break;
                }
            }
        }

        if (empty($keysToRemove)) {
            $this->info('No redundant flat keys found.');
            return;
        }

        $this->info('Found redundant flat keys: ' . implode(', ', $keysToRemove));

        if ($this->confirm('Do you want to remove these keys and their associated data?')) {
            DB::transaction(function () use ($keysToRemove, $storeTypeId) {
                // Remove from ProductSpecificationInfo
                ProductSpecificationInfo::where('store_type_id', $storeTypeId)
                    ->whereIn('field_name', $keysToRemove)
                    ->delete();

                // Remove from ListingRequirement
                ListingRequirement::where('store_type_id', $storeTypeId)
                    ->whereIn('key_value', $keysToRemove)
                    ->delete();

                // Remove from ProductSpecification (the actual values saved for products)
                // Note: We might want to migrate values, but since the structure changed from 
                // string to object/array, simple deletion is safer as the seeder will recreate 
                // the correct requirement structure.
                ProductSpecification::whereIn('name', $keysToRemove)
                    ->where('store_type_id', $storeTypeId)
                    ->delete();
            });

            $this->info('Cleanup completed successfully.');
        }
    }
}
