<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\ProductType;
use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategorySyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

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
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Category'
        ]);

        try {
            $ctagories = DB::connection('fonus_db')->table('tbl_category')->get();
            foreach ($ctagories as $category) {
                 Log::info("Id :: " . $category->category_id);
                $this->createCategory($category);
            }

            $subCategories = DB::connection('fonus_db')->table('tbl_category_sub')->get();
            foreach ($subCategories as $subCategory) {
                $this->createSubCategory($subCategory);
            }

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Category Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createCategory(mixed $fonusCategory)
    {

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

            ProductType::updateOrCreate(
                [
                    'code' => $fonusCategory->category_id,
                    'parent_id' => null
                ],
                [
                    'name' => $fonusCategory->category,
                    'code' => $fonusCategory->category_id,
                    'image_id' => $imageDetail->id ?? null,
                    'parent_id' => null,
                    'singular_name' => $fonusCategory->category,
                    'sort_order' => $fonusCategory->order_no,
                ]
            );
        }
    }

    private function createSubCategory(mixed $subCategory)
    {
        $category = ProductType::where('code', $subCategory->category_id)->first();
        if (!isset($category)) {
            return;
        }

        ProductType::updateOrCreate(
            [
                'code' => $subCategory->sub_category_id
            ],
            [
                'name' => $subCategory->sub_category,
                'code' => $subCategory->sub_category_id,
                'image_id' => null,
                'parent_id' => $category->id,
                'singular_name' => $subCategory->sub_category,
                'sort_order' => $subCategory->order_no,
            ]
        );
    }
}
