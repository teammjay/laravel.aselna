<?php

namespace Database\Seeders\Walmart;

use App\Models\StoreType;
use App\Services\Auth\WalmartAuthService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddStoreCategories extends Seeder
{
    private WalmartAuthService $walmartAuthService;
    private StoreType $storeType;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->walmartAuthService = new WalmartAuthService('b8f63c96-93fd-4bc1-b0cc-2a5f84e5bd7c','AOWo7abtyndXMFjcvzlnP6Z_62SgQPZRtquhIunLHMiNYQggxowM22wipJhZICtgShCafih2apKPf4XGPTpR_2g');
        $this->storeType = StoreType::where('code', 'walmart')->first();
        $this->fetchCategories();
    }

    private function fetchCategories()
    {
        $storeCategories = $this->walmartAuthService->fetchStoreCategories('MP_ITEM','5.0.20250828-17_32_59-api');

        if (isset($storeCategories) && is_array($storeCategories['itemTaxonomy'])) {
            foreach ($storeCategories['itemTaxonomy'] as $category) {
                if ($category['description'] == 'Electronics' && is_array($category['productTypeGroup'])) {
                    foreach ($category['productTypeGroup'] as $subCategory) {
                        if (isset($subCategory['productType']) && is_array($subCategory['productType'])) {
                            foreach ($subCategory['productType'] as $childCategory) {
                                \App\Models\StoreTypeCategory::updateOrCreate([
                                    'store_type_code' => $childCategory['productTypeName'],
                                    'code' => $childCategory['productTypeName'],
                                    'store_type_id' => $this->storeType->id,
                                ], [
                                    'store_type_code' => $childCategory['productTypeName'],
                                    'code' => $childCategory['productTypeName'],
                                    'name' => $childCategory['productTypeName'],
                                    'store_type_id' => $this->storeType->id,
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }
}
