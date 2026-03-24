<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\Catalog;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class OdemobileListingItemSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3600;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Odemobile Listing Item Sync']);

        try {
            // Find all local stores with "odemobile" in their name (case-insensitive)
            $odemobileStores = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->whereIn('store.name',['OdeMobile'])
                ->select('store.id', 'store.name')
                ->get();

            if ($odemobileStores->isEmpty()) {
                Log::info("No Odemobile stores found for sync.");
            }

            foreach ($odemobileStores as $store) {
                Log::info("Starting Odemobile Listing Item Sync for Store: " . $store->name . " (" . $store->id . ")");

                // Query Oydera database for listing items belonging to this store
                // We join with store_product and product to get the reference_id1 (product_code)
                $listingItemsQ = DB::connection('easyesale_db')
                    ->table('listing_item as li')
                    ->join('store_product as sp', 'sp.id', '=', 'li.store_product_id')
                    ->join('product as p', 'p.id', '=', 'sp.product_id')
                    ->where('sp.store_id', $store->id)
                    ->whereNotNull('p.reference_id1')
                    ->select('li.*', 'p.reference_id1 as oydera_product_code')
                    ->orderBy('li.id');

                $listingItemsQ->chunk(500, function ($oyderaItems) use ($store) {
                    Log::info("Processing chunk of " . count($oyderaItems) . " listing items for Store: " . $store->name);
                    $ids = $oyderaItems->pluck('id')->toArray();
                    // Load existing local listing items with their store products
                    $localItems = ListingItem::whereIn('id', $ids)->with('storeProduct')->get()->keyBy('id');

                    // Collect all product codes from Oydera items to find local products
                    $productCodes = $oyderaItems->pluck('oydera_product_code')->filter()->unique()->toArray();
                    $localProducts = Catalog::whereIn('product_code', $productCodes)->get()->keyBy('product_code');

                    // Load local store products for this store and the identified products
                    $localStoreProducts = StoreProduct::where('store_id', $store->id)
                        ->whereIn('product_id', $localProducts->pluck('product_id'))
                        ->get()
                        ->keyBy('product_id');

                    foreach ($oyderaItems as $oyderaItem) {
                        // Requirement: Sync only those whose product id is not matching
                        $existingItem = $localItems->get($oyderaItem->id);
                        if ($existingItem) {
                            continue;
                        }
                        $productCode = $oyderaItem->oydera_product_code;
                        if (!$productCode) {
                            continue;
                        }

                        $localProduct = $localProducts->get($productCode);
                        if (!$localProduct) {
                            // Product doesn't exist locally, so we can't link the listing item
                            continue;
                        }

                        $localStoreProduct = $localStoreProducts->get($localProduct->product_id);

                        // If store product is not there, we create it
                        if (!$localStoreProduct) {
                            $oyderaStoreProduct = DB::connection('easyesale_db')
                                ->table('store_product')
                                ->where('id', $oyderaItem->store_product_id)
                                ->first();

                            if ($oyderaStoreProduct) {
                                $localStoreProduct = StoreProduct::create([
                                    'product_id' => $localProduct->product_id,
                                    'store_id' => $store->id,
                                    'active' => $oyderaStoreProduct->active ? 1 : 0,
                                    'price' => $oyderaStoreProduct->price,
                                    'msrp' => $oyderaStoreProduct->msrp,
                                    'description' => $oyderaStoreProduct->description,
                                    'title' => $oyderaStoreProduct->title,
                                    'store_type_category_id' => $oyderaStoreProduct->channel_category_id,
                                    'created_by' => 'system',
                                    'updated_by' => 'system',
                                ]);
                                // Cache it for current chunk
                                $localStoreProducts->put($localProduct->product_id, $localStoreProduct);
                            } else {
                                Log::error("Store Product ID: {$oyderaItem->store_product_id} not found in Oydera for Listing Item ID: {$oyderaItem->id}");
                                continue;
                            }
                        }

                        $this->upsertListingItem($oyderaItem, $localStoreProduct->id);
                    }
                });
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            Log::error("OdemobileListingItemSyncJob Failed: " . $e->getMessage());

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Odemobile Listing Item Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine(),
                'method' => __METHOD__
            ]);
        }
    }

    /**
     * Update or create the listing item locally.
     */
    private function upsertListingItem(mixed $oyderaItem, string $localStoreProductId): void
    {
        Log::info("Upserting Listing Item ID: {$oyderaItem->id} for Store Product ID: {$localStoreProductId}");
        ListingItem::updateOrCreate([
            'id' => $oyderaItem->id
        ], [
            'id' => $oyderaItem->id,
            'store_type_listing_id' => $oyderaItem->channel_listing_id,
            'title' => $oyderaItem->title,
            'description' => $oyderaItem->description,
            'sku' => $oyderaItem->sku,
            'store_sku' => $oyderaItem->store_sku,
            'store_url' => $oyderaItem->store_url,
            'parent_id' => $oyderaItem->parent_id,
            'price' => $oyderaItem->price,
            'sync_status' => $oyderaItem->sync_status,
            'processing_days' => $oyderaItem->processing_days,
            'quantity' => $oyderaItem->quantity,
            'creation_order' => $oyderaItem->creation_order,
            'creation_retry_count' => $oyderaItem->creation_retry_count,
            'linked_item_id' => $oyderaItem->linked_item_id,
            'carrier_id' => $oyderaItem->carrier_id,
            'device_id' => $oyderaItem->device_id,
            'store_product_id' => $localStoreProductId,
            'generation_type_id' => $oyderaItem->cea_generation_type_id,
            'brand_id' => $oyderaItem->cea_brand_id,
            'device_carrier_id' => $oyderaItem->device_carrier_id,
            'color_id' => $oyderaItem->color_id,
            'size_id' => $oyderaItem->size_id,
            'status' => $oyderaItem->status,
            'client' => $oyderaItem->client,
            'upc' => $oyderaItem->upc,
            'brand_combo' => $oyderaItem->brand_combo,
            'carrier_combo' => $oyderaItem->carrier_combo,
            'submit_count' => $oyderaItem->submit_count,
            'replaced_id' => $oyderaItem->replaced_id,
            'sync_message' => $oyderaItem->sync_message,
            'mpn' => $oyderaItem->mpn,
            'manufacturer_id' => $oyderaItem->manufacturer_id,
            'sales_count' => $oyderaItem->sales_count,
            'last_sale_date' => $oyderaItem->last_sale_date,
            'image_version' => $oyderaItem->image_version,
            'fulfillment_type' => $oyderaItem->fulfillment_type,
            'overwrite_price' => $oyderaItem->overwrite_price,
            'overwrite_msrp' => $oyderaItem->overwrite_msrp,
            'overwrite_compare_at_price' => $oyderaItem->overwrite_compare_at_price,
            'overwrite_title' => $oyderaItem->overwrite_title,
            'overwrite_description' => $oyderaItem->overwrite_description,
            'created_by' => 'system',
            'updated_by' => 'system',
            'updated_at' => now()
        ]);
    }
}
