<?php

namespace App\Jobs\Oydera;

use App\Constants\ListingStatus;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyListingProductsSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 600000;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Listing Products']);

        try {
            $storeIds = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->whereIn('store.name', ['UZID'])
                ->pluck('store.id');

            DB::connection('easyesale_db')->table('listing_item')
                ->whereIn('store_product_id', function ($query) use ($storeIds) {
                    $query->select('store_product.id')
                        ->from('store_product')
                        ->whereIn('store_id', $storeIds);
                })
                ->orderBy('id')
                ->chunk(100, function ($items) {
                    foreach ($items as $item) {
                        $this->syncListingItem($item);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Shopify Listing Products Sync Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function syncListingItem(mixed $item)
    {
        Log::info('Syncing listing item: ' . $item->id);
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
                'status' => $this->getStatus($item->status),
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

//        $listingItemDataQ = DB::connection('easyesale_db')->table('cea_listing_item_detail')
//            ->where('listing_item_id', $item->id)
//            ->orderBy('id');
//
//        foreach ($listingItemDataQ->cursor() as $listingItemData) {
//            $this->createListingItemData($listingItemData);
//        }
    }

    private function createListingItemData(mixed $listingItemData)
    {
        if (ListingItemDetail::where('id', $listingItemData->id)->exists()) {
            return;
        }
        ListingItemDetail::updateOrCreate([
            'id' => $listingItemData->id
        ], [
            'id' => $listingItemData->id,
            'sku' => $listingItemData->sku,
            'title' => $listingItemData->title,
            'upc' => $listingItemData->upc,
            'url' => $listingItemData->url,
            'store_sku' => $listingItemData->store_sku,
            'kicked' => $listingItemData->kicked,
            'replaced_id' => $listingItemData->replaced_id,
            'device_id' => $listingItemData->device_id,
            'device_carrier_id' => $listingItemData->device_carrier_id,
            'listing_item_id' => $listingItemData->listing_item_id,
            'last_sale_date' => $listingItemData->last_sale_date,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function getStatus($status)
    {
        switch ($status) {
            case 0:
                return ListingStatus::Active;
            case 1:
                return ListingStatus::Suspended;
            case 2:
                return ListingStatus::Deleted;
            default:
                return ListingStatus::Deleted;
        }
    }
}
