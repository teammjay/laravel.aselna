<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\ProductType;
use App\Models\StoreType;
use App\Models\StoreTypeCategories;
use App\Models\StoreTypeCategory;
use App\Models\SubCategory;
use App\Utils\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategorySyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

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
        $distinctParentIds = DB::connection('easyesale_db')->table('product_type')->whereNull('parent_id')->cursor();
        //[{"parent_id":"7a099033-9940-ec5e-30bc-1511d3fc88ea"} I want to convert into array of values
        $distinctIds = [];
        foreach ($distinctParentIds as $distinctId) {
            // Log::info("Id :: " . $distinctId->id);
            $distinctIds[] = $distinctId->id;
        }
        $queue = new Queue();

        $categories = DB::connection('easyesale_db')->table('product_type')->whereIn('id', $distinctIds)->cursor();
        foreach ($categories as $category) {
            $queue->enqueue($category->id);
            $this->createProductType($category);
        }

        while ($queue->size() > 0) {
            $nextId = $queue->peek();
            $queue->dequeue();
            $categories = DB::connection('easyesale_db')->table('product_type')->where('parent_id', $nextId)->cursor();
            foreach ($categories as $category) {
                $queue->enqueue($category->id);
                $this->createProductType($category);
            }
        }

        $queue = new Queue();
        $storeTypeCategories = DB::connection('easyesale_db')->table('channel_category')->whereNull('parent_id')->cursor();
        foreach ($storeTypeCategories as $storeTypeCategory) {
            $queue->enqueue($storeTypeCategory->id);
            $this->createStoreTypeCategories($storeTypeCategory);
        }

        while ($queue->size() > 0) {
            $nextId = $queue->peek();
            $queue->dequeue();
            $storeTypeCategories = DB::connection('easyesale_db')->table('channel_category')->where('parent_id',$nextId)->cursor();
            foreach ($storeTypeCategories as $storeTypeCategory) {
                $queue->enqueue($storeTypeCategory->id);
                $this->createStoreTypeCategories($storeTypeCategory);
            }
        }
    }

    private function createProductType(mixed $category)
    {
        $category = ProductType::updateOrCreate(
            ['id' => $category->id],
            [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'parent_id' => $category->parent_id ?? null,
                'singular_name' => $category->singular_name,
                'sort_order' => $category->sort_order,
            ]
        );
        Log::info('Category Created :: ' . $category);
    }

    private function createStoreTypeCategories(mixed $storeTypeCategory)
    {
        StoreTypeCategory::updateOrCreate(
            ['id' => $storeTypeCategory->id],
            [
                'id' => $storeTypeCategory->id,
                'store_type_id' => $storeTypeCategory->channel_id,
                'store_type_code' => $storeTypeCategory->channel_code,
                'parent_id' => $storeTypeCategory->parent_id ?? null,
                'name' => $storeTypeCategory->name,
                'code' => $storeTypeCategory->code
            ]
        );
    }
}
