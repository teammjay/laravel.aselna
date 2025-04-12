<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Services\Auth\MagentoAuthService;
use App\Services\Auth\ShopifyAuthService;
use App\Utils\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MarketplaceCategorySyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

    private mixed $storeId;
    private $store;

    /**
     * Create a new job instance.
     */
    public function __construct($storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Store Category Sync Job Started");
        $this->getVariables();
        switch ($this->store->storeType->code) {
            case 'shopify':
                $this->syncShopifyCategories();
                break;
            case 'magento':
                $this->syncMagentoCategories();
                break;
            default:
                Log::info("Store Type not found");
        }
    }

    private function getVariables()
    {
        $this->store = Store::find($this->storeId);
    }

    private function syncShopifyCategories()
    {
//        $shopifyAuthService = new ShopifyAuthService($this->store->secret_key);

        $queue = new Queue();
        $parentCategories = StoreCategory::where('store_id', $this->storeId)->whereNull('parent_id')->get();
        foreach ($parentCategories as $parentCategory) {
            $queue->enqueue($parentCategory->id);
            $this->createShopifyCategory($parentCategory);
        }

        while ($queue->size() > 0) {
            $nextId = $queue->peek();
            $queue->dequeue();
            $categories = StoreCategory::where('parent_id', $nextId)->get();
            foreach ($categories as $category) {
                $queue->enqueue($category->id);
                $this->createShopifyCategory($category);
            }
        }
    }

    private function syncMagentoCategories()
    {

        $queue = new Queue();
        $parentCategories = StoreCategory::where('store_id', $this->storeId)->whereNull('parent_id')->get();
        foreach ($parentCategories as $parentCategory) {
            $queue->enqueue($parentCategory->id);
            $this->createMagentoCategory($parentCategory);
        }

        while ($queue->size() > 0) {
            $nextId = $queue->peek();
            $queue->dequeue();
            $categories = StoreCategory::where('parent_id', $nextId)->get();
            foreach ($categories as $category) {
                $queue->enqueue($category->id);
                $this->createMagentoCategory($category);
            }
        }
    }

    private function createMagentoCategory(mixed $category)
    {
        $magetoAuthService = new MagentoAuthService($this->store->seller_id, $this->store->secret_key);
        Log::info("Category parent ID :: " . $category->parent_id);
        $parentCategory = StoreCategory::find($category->parent_id);
        Log::info("Categiry Data :: " . $parentCategory);
        if (isset($category['sync_id'])) {
            return;
        }

        Log::info("Creating Category: " . json_encode($category));
        $response = $magetoAuthService->createCategory($category['name'],$parentCategory['sync_id'] ?? 2);

        $category->update([
//            'sync_id' => $response['id']
        ]);

//        Log::info("Category Created: " . json_encode($response));
    }

    private function createShopifyCategory(mixed $category)
    {

        $hopifyAuthService = new ShopifyAuthService($this->store->seller_id, $this->store->secret_key);
        $collectionData = [];
        $parentCategory = StoreCategory::find($category->parent_id);
        if (isset($category['sync_id'])) {
            return;
        }

        $collectionData['collection'] = [
            'title' => $parentCategory->name
        ];

        $response = $hopifyAuthService->createCollection($collectionData);
        Log::info("Testing the shopify creation of collections");

    }
}
