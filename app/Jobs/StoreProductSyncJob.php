<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\StoreProduct;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class StoreProductSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Store Products']);

        $storeProductsQ = DB::connection('easyesale_db')->table('store_product')->orderBy('id');

        if (env('APP_ENV') === 'local') {
            Log::info('Local Environment');
//            $storeProductsQ->whereRaw('id in (select store_product_id from oydera_listing_item where id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\'))');
        }

        try {
            $storeProductsQ->chunk(1000, function ($storeProducts) {
                foreach ($storeProducts as $storeProduct) {
                    $this->createStoreProduct($storeProduct);
                }
            });
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }


    private function createStoreProduct(mixed $storeProduct)
    {
        if (Catalog::where('product_id', $storeProduct->product_id)->doesntExist()) {
            return;
        }
        StoreProduct::updateOrCreate([
            'id' => $storeProduct->id,
        ], [
            'id' => $storeProduct->id,
            'product_id' => $storeProduct->product_id,
            'store_id' => $storeProduct->store_id,
            'active' => $storeProduct->active ? 1 : 0,
            'brand_combo' => $storeProduct->brand_combo,
            'carrier_combo' => $storeProduct->carrier_combo,
            'store_type_category_id' => $storeProduct->channel_category_id,
            'client' => $storeProduct->client,
            'compare_at_price' => $storeProduct->compare_at_price,
            'description' => $storeProduct->description,
            'errors' => $storeProduct->errors,
            'fulfillment_by_channel_price' => $storeProduct->fulfillment_by_channel_price,
            'fulfillment_type' => $storeProduct->fulfillment_type,
            'include_carriers' => $storeProduct->include_carriers,
            'last_sale_date' => $storeProduct->last_sale_date,
            'last_synch_date_time' => $storeProduct->last_synch_date_time,
            'last_update_date_time' => $storeProduct->last_update_date_time,
            'listing_generation_type_id' => $storeProduct->listing_generation_type_id,
            'listing_title_template_id' => $storeProduct->listing_title_template_id,
//            'long_tail_keywords' => $storeProduct,
            'manufacturer_id' => $storeProduct->manufacturer_id,
            'msrp' => $storeProduct->msrp,
            'overwrite_compare_at_price' => $storeProduct->overwrite_compare_at_price,
            'overwrite_description' => $storeProduct->overwrite_description,
            'overwrite_fulfillment_by_channel_price' => $storeProduct->overwrite_fulfillment_by_channel_price,
            'overwrite_msrp' => $storeProduct->overwrite_msrp,
            'overwrite_price' => $storeProduct->overwrite_price,
            'overwrite_title' => $storeProduct->overwrite_title,
            'overwrite_wholesale_price' => $storeProduct->overwrite_wholesale_price,
            'preserve_title_description' => $storeProduct->preserve_title_description,
            'price' => $storeProduct->price,
            'profit_rate' => $storeProduct->profit_rate,
            'sales_count' => $storeProduct->sales_count,
            'short_tail_keywords' => $storeProduct->short_tail_keywords,
//            'store_category_id' => $storeProduct->store_category_id ,
            'synch_inventory' => $storeProduct->synch_inventory,
            'synch_item' => $storeProduct->synch_item,
            'synch_price' => $storeProduct->synch_price,
            'title' => $storeProduct->title,
//            'wholesale_price' => $storeProduct,

            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
