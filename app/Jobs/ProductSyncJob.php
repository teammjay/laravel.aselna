<?php

namespace App\Jobs;

use App\Models\Catalog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductSyncJob implements ShouldQueue
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
        $products = DB::connection('fonus_db')->table('tbl_product')->get();
        foreach ($products as $product) {
            if (Catalog::where(['product_code' => $product->product_id])->exists()) continue;

            $this->createProduct($product);

            $this->createProductTitleKeywords($product);
            break;
        }
    }

    private function createProduct($product)
    {
        $newProduct = Catalog::updateOrInsert(
            ['product_code' => $product->product_id],
            [
                'product_id' => (string) Str::uuid(),
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
                'product_sku' => $product->custom_label,
                'product_status' => $product->status_id == 1 ? 'active' : 'inactive',
                'product_color' => $product->color,
                'product_code' => $product->product_id,

                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // create product Title Keywords

    }

    private function createProductTitleKeywords($product)
    {
        $keywords = DB::connection('fonus_db')->table('tbl_title_keywords')->where('product_id', $product->product_id)->get();

        $product_id = Catalog::where('product_code', $product->product_id)->first();

        foreach ($keywords as $keyword) {
            if (DB::table('product_title_keyword')->where('keyword', $keyword->title_keyword)->exists()) continue;
            DB::table('product_title_keyword')->insert([
                'id' => (string) Str::uuid(),
                'keyword' => $keyword->title_keyword,
                'priority' => $keyword->order_id,
                'product_id' => $product_id->product_id,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
