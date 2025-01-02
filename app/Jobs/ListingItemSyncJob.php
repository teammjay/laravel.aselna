<?php

namespace App\Jobs;

use App\Models\ListingItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ListingItemSyncJob implements ShouldQueue
{
    use Queueable;

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
        $listingItems = DB::connection('easyesale_db')->table('listing_item')->cursor();
        foreach ($listingItems as $listingItem) {
            $this->createListingItem($listingItem);
        }
    }

    private function createListingItem(mixed $listingItem)
    {
        ListingItem::updateOrCreate([
            'id' => $listingItem->id
        ], [
            'id' => $listingItem->id,
            'store_type_listing_id' => $listingItem->channel_listing_id,
            'title' => $listingItem->title,
            'description' => $listingItem->description,
            'sku' => $listingItem->sku,
            'store_sku' => $listingItem->store_sku,
            'store_url' => $listingItem->store_url,
            'parent_id' => $listingItem->parent_id,
            'price' => $listingItem->price,
            'sync_status' => $listingItem->sync_status,
            'processing_days' => $listingItem->processing_days,
            'quantity' => $listingItem->quantity,
            'creation_order' => $listingItem->creation_order,
            'creation_retry_count' => $listingItem->creation_retry_count,
            'linked_item_id' => $listingItem->linked_item_id,
            'carrier_id' => $listingItem->carrier_id,
            'device_id' => $listingItem->device_id,
            'store_product_id' => $listingItem->store_product_id,
            'generation_type_id' => $listingItem->cea_generation_type_id,
            'brand_id' => $listingItem->cea_brand_id,
            'device_carrier_id' => $listingItem->device_carrier_id,
            'color_id' => $listingItem->color_id,
            'size_id' => $listingItem->size_id,
            'status' => $listingItem->status,
            'client' => $listingItem->client,
            'upc' => $listingItem->upc,
            'brand_combo' => $listingItem->brand_combo,
            'carrier_combo' => $listingItem->carrier_combo,
            'submit_count' => $listingItem->submit_count,
            'replaced_id' => $listingItem->replaced_id,
            'sync_message' => $listingItem->sync_message,
            'mpn' => $listingItem->mpn,
            'manufacturer_id' => $listingItem->manufacturer_id,
            'sales_count' => $listingItem->sales_count,
            'last_sale_date' => $listingItem->last_sale_date,
            'image_version' => $listingItem->image_version,
            'fulfillment_type' => $listingItem->fulfillment_type,
            'overwrite_price' => $listingItem->overwrite_price,
            'overwrite_msrp' => $listingItem->overwrite_msrp,
            'overwrite_compare_at_price' => $listingItem->overwrite_compare_at_price,
            'overwrite_title' => $listingItem->overwrite_title,
            'overwrite_description' => $listingItem->overwrite_description,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
