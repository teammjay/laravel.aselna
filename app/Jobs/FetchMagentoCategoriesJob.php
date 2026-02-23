<?php

namespace App\Jobs;

use App\Models\MarketPlaceCategoryMappings;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\Auth\MagentoAuthService;
use App\Services\Marketplace\Service\MagentoService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class FetchMagentoCategoriesJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Magento Categories']);

        try {
            $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'magento')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    Log::info('Store : ' . $store->name);
                    $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);
                    $categoryResponse = $magentoAuthService->getAllCategories();

                    Log::info('Category Response: ' . json_encode($categoryResponse));

                    $this->syncCategories($categoryResponse['children_data'], $store);
                }
            }
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Fetch Magento Categories Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

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
