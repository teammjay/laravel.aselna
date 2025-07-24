<?php

namespace App\Jobs;

use App\Models\MarketPlaceCategoryMappings;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\Auth\MagentoAuthService;
use App\Services\Marketplace\Service\MagentoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchMagentoCategoriesJob implements ShouldQueue
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
        $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->get();

        if (count($stores ?? []) > 0) {
            foreach ($stores as $store) {
                Log::info('Store : ' . $store->name);
                $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key);
                $categoryResponse = $magentoAuthService->getAllCategories();

                Log::info('Category Response: ' . json_encode($categoryResponse));

                $this->syncCategories($categoryResponse['children_data'], $store);
            }
        }
    }

    private function syncCategories($categories, $store)
    {
        foreach ($categories as $category) {
            $storeCategory = StoreCategory::where('store_id', $store->id)
                ->where('name', $category['name'])
                ->first();

            MarketPlaceCategoryMappings::updateOrCreate(
                [
                    'marketplace_category_id' => $category['id'],
                    'store_id' => $store->id,
                ],
                [
                    'store_id' => $store->id,
                    'name' => $category['name'],
                    'category_id' => $storeCategory->id ?? null,
                    'marketplace_category_id' => $category['id'] ?? null,
                    'marketplace_parent_id' => $category['parent_id'] ?? null,
                ]
            );
            $this->syncCategories($category['children_data'], $store);
        }
    }
}
