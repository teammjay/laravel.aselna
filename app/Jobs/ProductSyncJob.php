<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Manufacturer;
use App\Models\ProductKeyFeatures;
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

            $product_id = Catalog::where('product_code', $product->product_id)->first();

            $this->createProductTitleKeywords($product, $product_id);

            $this->createProductFeatures($product, $product_id);
        }
    }

    private function createProduct($product)
    {
        $manufacturer = Manufacturer::where('code', $product->manufacturer_id)->first();
        $newProduct = Catalog::updateOrInsert(
            ['product_code' => $product->product_id],
            [
                'product_id' => (string)Str::uuid(),
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
                'product_sku' => $product->custom_label,
                'product_status' => $product->status_id == 1 ? 'active' : 'discontinued',
                'product_color' => $product->color,
                'product_code' => $product->product_id,
                'product_weight' => $product->weight,
                'product_price' => $product->website_price,
                'product_wholesale_price' => $product->wholesale_price,
                'product_marketplace_price' => $product->marketplace_price,
                'product_mrsp' => $product->msrp,
                'manufacturer_id' => $manufacturer->id,

                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function createProductTitleKeywords($product, $product_id)
    {
        $keywords = DB::connection('fonus_db')->table('tbl_title_keywords')->where('product_id', $product->product_id)->get();

        foreach ($keywords as $keyword) {
            if (DB::table('product_title_keyword')->where('keyword', $keyword->title_keyword)->exists()) continue;
            DB::table('product_title_keyword')->insert([
                'id' => (string)Str::uuid(),
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

    private function createProductFeatures(mixed $product, $product_id)
    {
        $bullet_points = [
            '1' => $product->bullet_point1,
            '2' => $product->bullet_point2,
            '3' => $product->bullet_point3,
            '4' => $product->bullet_point4,
            '5' => $product->bullet_point5,
            '6' => $product->bullet_point6,
        ];
        foreach ($bullet_points as $key => $value) {
            ProductKeyFeatures::updateOrInsert(
                ['product_id' => $product_id->product_id, 'sort_order' => $key],
                [
                    'id' => (string)Str::uuid(),
                    'product_id' => $product_id->product_id,
                    'value' => $value,
                    'sort_order' => $key,
                ]
            );
        }
    }
}
