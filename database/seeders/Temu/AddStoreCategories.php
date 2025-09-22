<?php

namespace Database\Seeders\Temu;

use App\Models\StoreCategory;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\Auth\TemuAuthService;
use App\Utils\Queue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class AddStoreCategories extends Seeder
{
    private TemuAuthService $temuAuthService;
    private StoreType $storeType;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->temuAuthService = new TemuAuthService();
        $this->storeType = StoreType::where('code', 'temu')->first();
        $this->fetchCategories(2542);
        $this->fetchCategories(24252);
    }


    private function fetchCategories($parentId = 0, $dbParentId = null)
    {
        Log::info('Fetching categories for parent ID: ' . $parentId);
        $categories = $this->temuAuthService->getCategories($parentId);
        Log::info('Fetched categories: ' . json_encode($categories));

        if (isset($categories['result']) && is_array($categories['result']) && isset($categories['result']['goodsCatsList'])) {
            foreach ($categories['result']['goodsCatsList'] as $category) {
                Log::info('Processing category: ' . $category['catName']);
                $createdCategory = StoreTypeCategory::updateOrCreate([
                    'store_type_code' => $category['catId'],
                    'code' => $category['catId'],
                    'store_type_id' => $this->storeType->id,
                ], [
                    'store_type_code' => $category['catId'],
                    'code' => $category['catId'],
                    'name' => $category['catName'],
                    'store_type_id' => $this->storeType->id,
                    'parent_id' => $dbParentId,
                ]);

                // Recursively fetch subcategories
                $this->fetchCategories($category['catId'], $createdCategory->id);
            }
        }
    }
}
