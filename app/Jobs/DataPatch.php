<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductType;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DataPatch implements ShouldQueue
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
        $all_product_ids = [];
        $product_sub_types = ProductType::select('id', 'code')->whereIn('parent_id', function ($subquery) {
            $subquery->select('id')->from('product_type')->where('code', 'cea');
        })->get()->toArray();
        if (!empty($product_sub_types)) {
            foreach ($product_sub_types as $stype) {
                if ($stype['code'] === 'ceabundle') continue;
                $all_product_ids[] = $stype['id'];
                $product_sub_sub_types = ProductType::select('id')->where('parent_id', $stype['id'])->get()->toArray();
                if (!empty($product_sub_sub_types)) {
                    foreach ($product_sub_sub_types as $ssubtype) {
                        $all_product_ids[] = $ssubtype['id'];
                    }
                }
            }
        }
        $products = Catalog::where('product_status', '=', '1')->whereIn('products.product_type', $all_product_ids)->get();
        foreach ($products as $product) {
            $stores = Store::all();
            foreach ($stores as $store) {
                $store_product = StoreProduct::where('store_id', $store->id)->where('product_id', $product->product_id)->first();
                if (empty($store_product)) {
                    $store_product = new StoreProduct();
                    $store_product->store_id = $store->id;
                    $store_product->product_id = $product->product_id;
                    $store_product->save();
                }
            }
        }


    }
}
