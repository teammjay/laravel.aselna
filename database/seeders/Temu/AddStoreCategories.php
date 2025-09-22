<?php

namespace Database\Seeders\Temu;

use App\Models\StoreCategory;
use App\Models\StoreType;
use App\Services\Auth\TemuAuthService;
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
        $this->fetchCategories();
    }


    private function fetchCategories($parentId = 0, $dbParentId = null)
    {
        Log::info('Fetching categories for parent ID: ' . $parentId);
        $categories = $this->temuAuthService->getCategories($parentId);
        Log::info('Fetched categories: ' . json_encode($categories));

        if (isset($categories['result']) && is_array($categories['result'])) {
            foreach ($categories['result']['goodsCatsList'] as $category) {
                Log::info('Processing category: ' . $category['catName']);
                $createdCategory = StoreCategory::updateOrCreate([
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
                $this->fetchCategories($category['cat_id'], $createdCategory->id);
            }
        }
    }
}
