<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopifyListingProductsSyncJob implements ShouldQueue
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
        $storeIds = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'shopify')
            ->pluck('store.id');
        DB::connection('easyesale_db')->table('listing_item')
            ->whereIn('store_product_id', function ($query) use ($storeIds) {
                $query->select('store_product.id')
                    ->from('store_product')
                    ->whereIn('store_id',$storeIds);
            })
            ->orderBy('id')
            ->chunk(100, function ($items) {
                foreach ($items as $item) {
                    $this->syncListingItem($item);
                }
            });
    }

    private function syncListingItem(mixed $item)
    {
        Log::info('Syncing listing item: ' . $item->id);
        if (ListingItem::where('id', $item->id)->exists()) {
            return;
        }
        ListingItem::updateOrCreate(
            [
                'id' => $item->id,
            ],
            [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'sku' => $item->sku,
                'store_sku' => $item->store_sku,
                'price' => $item->price,
                'sync_status' => $item->sync_status,
                'quantity' => $item->quantity,
                'store_product_id' => $item->store_product_id,
                'status' => $item->sync_status,
                'store_url' => $item->store_url,
                'created_at' => $item->create_ts,
                'parent_id' => $item->parent_id,
                'processing_days' => $item->processing_days,
                'creation_order' => $item->creation_order,
                'creation_retry_count' => $item->creation_retry_count,
                'linked_item_id' => $item->linked_item_id,
                'carrier_id' => $item->carrier_id,
                'device_id' => $item->device_id,
                'generation_type_id' => $item->cea_generation_type_id,
                'brand_id' => $item->cea_brand_id,
                'device_carrier_id' => $item->device_carrier_id,
                'color_id' => $item->color_id,
                'size_id' => $item->size_id,
                'client' => $item->client,
                'upc' => $item->upc,
                'brand_combo' => $item->brand_combo,
                'carrier_combo' => $item->carrier_combo,
                'submit_count' => $item->submit_count,
                'replaced_id' => $item->replaced_id,
                'sync_message' => $item->sync_message,
                'mpn' => $item->mpn,
                'manufacturer_id' => $item->manufacturer_id,
                'sales_count' => $item->sales_count,
                'last_sale_date' => $item->last_sale_date,
                'image_version' => $item->image_version,
                'fulfillment_type' => $item->fulfillment_type,
                'overwrite_price' => $item->overwrite_price,
                'overwrite_msrp' => $item->overwrite_msrp,
                'overwrite_compare_at_price' => $item->overwrite_compare_at_price,
                'overwrite_title' => $item->overwrite_title,
                'overwrite_description' => $item->overwrite_description,
            ]
        );
    }
}
