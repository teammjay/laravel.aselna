<?php

namespace App\Jobs;

use App\Models\Catalog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
//            Log::info('Product Json :: ' . json_encode($products));
            $this->createProduct($product);
            break;
        }
    }

    private function createProduct($product)
    {
        Catalog::updateOrInsert(
            ['product_code' => $product->product_id],
            [
                'product_title' => $product->product_name,
                'product_description' => $product->long_description,
                'product_sku' => $product->custom_label,
                'product_status' => $product->status_id == 1 ? 'active' : 'inactive',
                'product_color' => $product->color,

                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
