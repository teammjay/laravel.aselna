<?php

namespace App\Jobs;

use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\StoreType;
use App\Models\StoreTypeCategory;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class StoreCategorySyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    private $storeID;
    private $store;

    /**
     * Create a new job instance.
     */
    public function __construct($storeID)
    {
        $this->storeID = $storeID;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Store Category']);

        Log::info("Store Category Sync Job Started");

        try {
            $this->getVariables();
            $this->syncCategories();

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Store Category Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['storeId' => $this->storeID]);
        }
    }

    private function getVariables()
    {
        $this->store = Store::find($this->storeID);
    }

    private function syncCategories()
    {
        $categoriesQ = ProductType::whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->orderBy('name', 'asc')->get();
        foreach ($categoriesQ as $category) {
            $createdId = $this->createCategory($category);
            $this->syncSubCategories($category, $createdId);
        }
    }

    private function createCategory(mixed $category, $parentID = null)
    {
        //Log::info("Category :: " . json_encode($category));
        $storeCategory = StoreCategory::updateOrCreate(
            [
                'store_id' => $this->storeID,
                'code' => $category->code,
            ], [
                'store_id' => $this->storeID,
                'name' => $category->name,
                'code' => $category->code,
                'parent_id' => $parentID,
                'updated_by' => 'system',
            ]
        );

        return $storeCategory->id;
    }

    private function syncSubCategories(mixed $category, mixed $createdId)
    {
        $categoriesQ = ProductType::where('parent_id', $category->id)->get();
        foreach ($categoriesQ as $subCategory) {
            $newCreatedId = $this->createCategory($subCategory, $createdId);
            $this->syncSubCategories($subCategory, $newCreatedId);
        }
    }
}
