<?php

namespace App\Jobs;

use App\Models\ListingItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ListingItemSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $listingItemsQ = DB::connection('easyesale_db')
            ->table('listing_item')
            // only on local
            ->orderBy('id');

        if (env('APP_ENV') == 'local') {
            Log::info("Came tHEre");
            $listingItemsQ->whereRaw('id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\')');
//            $listingItemsQ->whereRaw('store_product_id in (select id from oydera_store_product where product_id in (select id from oydera_product where oem_generic = 2))');
        } else {
            // get Last 2 years only
//            $listingItemsQ->where('create_ts', '>=', now()->subYears(2));
        }

        $listingItemsQ->chunk(1000, function ($listingItems) {
            foreach ($listingItems as $listingItem) {
                $this->createListingItem($listingItem);
            }
        });
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
