<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Models\StoreProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ShopifyDuplicateStoreProductsJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 2400;

    protected $fromStoreId;
    protected $toStoreId;

    protected $store;
    protected $toStore;

    /**
     * Create a new job instance.
     */
    public function __construct($storeId, $toStoreId)
    {
        $this->fromStoreId = $storeId;
        $this->toStoreId = $toStoreId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->setVariables();

        $this->duplicateStoreProducts();
    }

    private function setVariables()
    {
        $this->store = Store::find($this->fromStoreId);
        $this->toStore = Store::find($this->toStoreId);
    }

    private function duplicateStoreProducts()
    {
        StoreProduct::where('store_id', $this->fromStoreId)
            ->orderBy('id')
            ->limit(100)
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    Log::info("Processing Product ID: " . $product->id . " for Store ID: " . $this->fromStoreId);
                    $newProduct = $product->replicate();
                    $newProduct->store_id = $this->toStoreId;
                    $newProduct->created_at = now();
                    $newProduct->updated_at = now();
                    $newProduct->save();
                    Log::info("New Product ID: " . $newProduct->id . " for Store ID: " . $this->toStoreId);

                    $listingItem = ListingItem::where('store_product_id', $product->id)
                        ->where('sku', $product->sku)
                        ->first();
                    if ($listingItem) {
                        $newListingItem = $listingItem->replicate();
                        $newListingItem->store_product_id = $newProduct->id;
                        $newListingItem->created_at = now();
                        $newListingItem->updated_at = now();
                        $newListingItem->save();
                        Log::info("New Listing Item ID: " . $newListingItem->id . " for Store ID: " . $this->toStoreId);

                        $listingItemDetails = ListingItemDetail::where('listing_item_id', $listingItem->id)->get();
                        foreach ($listingItemDetails as $detail) {
                            $newDetail = $detail->replicate();
                            $newDetail->listing_item_id = $newListingItem->id;
                            $newDetail->created_at = now();
                            $newDetail->updated_at = now();
                            $newDetail->save();
                            Log::info("New Listing Item Detail ID: " . $newDetail->id . " for Listing Item ID: " . $newListingItem->id);
                        }
                    }
                }
            });
    }
}
