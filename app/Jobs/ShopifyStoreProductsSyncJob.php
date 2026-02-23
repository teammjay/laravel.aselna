<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ShopifyStoreProductsSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 30000;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Store Products']);

        try {
            $storeIds = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->whereIn('store.name', ['OdeMobile'])
                ->pluck('store.id');

            DB::connection('easyesale_db')->table('store_product')
                ->whereIn('store_id', $storeIds)
                ->orderBy('id')
                ->chunk(100, function ($products) {
                    foreach ($products as $product) {
                        $this->syncStoreProduct($product);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Shopify Store Products Sync Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncStoreProduct(mixed $storeProduct)
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
            'created_at' => $storeProduct->create_ts,
            'updated_at' => $storeProduct->update_ts
        ]);
    }
}
