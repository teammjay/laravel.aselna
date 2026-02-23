<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\StoreType;
use App\Models\StoreTypeCategories;
use App\Models\StoreTypeCategory;
use App\Models\SubCategory;
use App\Utils\FileUtility;
use App\Utils\Queue;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategorySyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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

        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Category']);
        try {

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
                $storeTypeCategories = DB::connection('easyesale_db')->table('channel_category')->where('parent_id', $nextId)->cursor();
                foreach ($storeTypeCategories as $storeTypeCategory) {
                    $queue->enqueue($storeTypeCategory->id);
                    $this->createStoreTypeCategories($storeTypeCategory);
                }
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Category Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    private function createProductType(mixed $category)
    {

        $fonusCategory = DB::connection('fonus_db')->table('tbl_category')->where('category_id',$category->code)->first();
        if (isset($fonusCategory)) {
            $imageName = $fonusCategory->picture1;
            $imageDetail = null;

            try {
                if(!isset($imageDetail)) {
                    Log::info('CategoryImagesSync: ' . $imageName);
                    $imagePath = env('FONUS_BASE_URL') . 'inv_images/category_images/' . $imageName;
                    $imageDetail = FileUtility::saveImage($imagePath, 'category_images', $imageName);
                }
            } catch (Exception $e) {

                \App\Helpers\ErrorLogger::log($e, __METHOD__);

                Log::error('ProductImagesSyncJob: ' . $e->getMessage());
            }
        }

        $createdCategory = ProductType::updateOrCreate(
            ['id' => $category->id],
            [
                'id' => $category->id,
                'name' => $category->name,
                'code' => $category->code,
                'image_id' => $imageDetail->id ?? null,
                'parent_id' => $category->parent_id ?? null,
                'singular_name' => $category->singular_name,
                'sort_order' => $category->sort_order,
            ]
        );

        Log::info('Category Created :: ' . $createdCategory);
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
