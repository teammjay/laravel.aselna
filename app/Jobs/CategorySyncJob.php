<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CategorySyncJob implements ShouldQueue
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
        $categories = DB::connection('fonus_db')->table('tbl_category')->get();
        foreach ($categories as $category) {
            if (Category::where(['code' => $category->category_id])->exists()) {
                continue;
            }
            $this->createCategory($category);
        }

        $subCategories = DB::connection('fonus_db')->table('tbl_category_sub')->get();
        foreach ($subCategories as $subCategory) {
            if (SubCategory::where(['code' => $subCategory->sub_category])->exists()) {
                continue;
            }
            $this->createSubCategory($subCategory);
        }
    }

    private function createCategory(mixed $category)
    {
        $newCategory = Category::updateOrInsert(
            ['code' => $category->category_id],
            [
                'name' => $category->category,
                'code' => $category->category_id,
                'notes1' => $category->notes1,
                'order' => $category->order_no,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function createSubCategory(mixed $subCategory)
    {
        // find category by category_id
        Log::info('Sub Category: ' . $subCategory->category_id);
        $category = Category::where('code', $subCategory->category_id)->first();
        if (!isset($category)) return;
        $newSubCategory = SubCategory::updateOrInsert(
            ['code' => $subCategory->sub_category],
            [
                'name' => $subCategory->sub_category,
                'code' => $subCategory->sub_category_id,
                'category_id' => $category->id ?? null,
                'notes1' => $subCategory->notes1,
                'order' => $subCategory->order_no,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
