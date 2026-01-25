<?php

namespace App\Jobs\Shopify;

use App\Constants\ListingStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncShopifyItems implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 300000;

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

        try {
            $storeIds = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->whereIn('store.name',['Odemobile'])
                ->pluck('store.id');
            // store products sync
            $this->syncStoreProducts($storeIds);
            // listing Item sync
//            $this->syncListingItems($storeIds);
            // listing Item data
            $this->syncListingItemDetails($storeIds);
        } catch (Exception $e) {

            CriticalErrorService::report('Store Products Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error($e->getMessage());
        }

    }

    private function syncStoreProducts($storeIds)
    {
        $storeProductsQ = DB::connection('easyesale_db')->table('store_product')->whereIn('store_id', $storeIds)->orderBy('id');
        $storeProductsQ->chunk(1000, function ($storeProducts) {
            foreach ($storeProducts as $storeProduct) {
                if (Catalog::where('product_id', $storeProduct->product_id)->doesntExist()) {
                    continue;
                }
                Log::info("Syncing Product ID: " . $storeProduct->id . " Product SKU: " . $storeProduct->product_id);
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
                    'synch_inventory' => $storeProduct->synch_inventory,
                    'synch_item' => $storeProduct->synch_item,
                    'synch_price' => $storeProduct->synch_price,
                    'title' => $storeProduct->title,
                    'created_by' => 'system',
                    'updated_by' => 'system',
                    'updated_at' => now(),
                ]);
            }
        });
    }

    private function syncListingItems($storeIds)
    {
        DB::connection('easyesale_db')->table('listing_item')
            ->whereIn('store_product_id', function ($query) use ($storeIds) {
                $query->select('store_product.id')
                    ->from('store_product')
                    ->whereIn('store_id', $storeIds);
            })
            ->orderBy('id')
            ->chunk(100, function ($items) {
                foreach ($items as $item) {
                    Log::info("Syncing Item ID: " . $item->id);
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
                }
            });
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

    private function syncListingItemDetails($storeIds)
    {
        $listingItems = ListingItem::whereIn('store_product_id', function ($query) use ($storeIds) {
            $query->select('store_product.id')
                ->from('store_product')
                ->whereIn('store_id', $storeIds);
        })->pluck('id');

        foreach ($listingItems as $listingItemId) {
            Log::info("Syncing Item ID: " . $listingItemId);
            $listingItemDataQ = DB::connection('easyesale_db')->table('cea_listing_item_detail')
                ->where('listing_item_id', $listingItemId)
                ->orderBy('id');

            foreach ($listingItemDataQ->get() as $listingItemData) {
                $this->createListingItemData($listingItemData);
            }
        }

    }

    private function createListingItemData(mixed $listingItemData)
    {
        if (ListingItemDetail::where('id', $listingItemData->id)->exists()) {
            return;
        }
        Log::info("Creating Item ID: " . $listingItemData->id);
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
}
