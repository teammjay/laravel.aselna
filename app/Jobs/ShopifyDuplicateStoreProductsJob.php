<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Models\StoreProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyDuplicateStoreProductsJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 600000;

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
        $this->updateTrackingRecord(['tags' => 'Shopify Duplicate Store Products']);

        $this->setVariables();

//        $this->duplicateStoreProducts();

//        $this->duplicateListingDetails();

//        $this->dublicateStoreNavigationPages();
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
                    if (StoreProduct::where('store_id', $this->toStoreId)->where('product_id',$product->product_id)->exists()) {
                        Log::info("Product with SKU: " . $product->sku . " already exists in Store ID: " . $this->toStoreId . ". Skipping duplication.");
                        continue;
                    }
                    Log::info("Processing Product ID: " . $product->id . " for Store ID: " . $this->fromStoreId);
                    $newProduct = $product->replicate();
                    $newProduct->store_id = $this->toStoreId;
                    $newProduct->created_at = now();
                    $newProduct->updated_at = now();
                    $newProduct->save();
                    Log::info("New Product ID: " . $newProduct->id . " for Store ID: " . $this->toStoreId);

                    $listingItem = ListingItem::where('store_product_id', $product->id)
                        ->first();
                    if ($listingItem) {
                        $newListingItem = $listingItem->replicate();
                        $newListingItem->store_product_id = $newProduct->id;
                        $newListingItem->created_at = now();
                        $newListingItem->updated_at = now();
                        $newListingItem->save();
                        Log::info("New Listing Item ID: " . $newListingItem->id . " for Store ID: " . $this->toStoreId);

//                        $listingItemDetails = ListingItemDetail::where('listing_item_id', $listingItem->id)->get();
//                        foreach ($listingItemDetails as $detail) {
//                            $newDetail = $detail->replicate();
//                            $newDetail->listing_item_id = $newListingItem->id;
//                            $newDetail->created_at = now();
//                            $newDetail->updated_at = now();
//                            $newDetail->save();
//                            Log::info("New Listing Item Detail ID: " . $newDetail->id . " for Listing Item ID: " . $newListingItem->id);
//                        }
                    }
                }
            });
    }

    private function duplicateListingDetails()
    {
        StoreProduct::where('store_id', $this->fromStoreId)
            ->orderBy('id')
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    Log::info("Processing Product ID: " . $product->id . " for Store ID: " . $this->fromStoreId);
                    $newStoreProduct = StoreProduct::where('store_id', $this->toStoreId)->where('product_id',$product->product_id)->first();
                    if(isset($newStoreProduct)) {
                        Log::info("Found corresponding Product ID: " . $newStoreProduct->id . " in Store ID: " . $this->toStoreId);
                        $newListingItem = ListingItem::where('store_product_id', $newStoreProduct->id)
                            ->first();
                        $oldListingItem = ListingItem::where('store_product_id', $product->id)
                            ->first();
                        if ($newListingItem) {
                            Log::info("Found Listing Item ID: " . $newListingItem->id . " for Store Product ID: " . $newStoreProduct->id);
                            $listingItemDetails = ListingItemDetail::where('listing_item_id', $oldListingItem->id)->get();
                            foreach ($listingItemDetails as $detail) {
                                if (ListingItemDetail::where('listing_item_id', $newListingItem->id)->where('sku', $detail->sku)->exists()) {
                                    Log::info("Listing Item Detail with SKU: " . $detail->sku . " already exists for Listing Item ID: " . $newListingItem->id . ". Skipping duplication.");
                                    continue;
                                }
                                $newDetail = $detail->replicate();
                                $newDetail->listing_item_id = $newListingItem->id;
                                $newDetail->created_at = now();
                                $newDetail->updated_at = now();
                                $newDetail->save();
                                Log::info("New Listing Item Detail ID: " . $newDetail->id . " for Listing Item ID: " . $newListingItem->id);
                            }
                        }
                    }
                }
            });
    }

    private function dublicateStoreNavigationPages()
    {
        StoreNavigationPage::where('store_id', $this->fromStoreId)
            ->orderBy('id')
            ->chunk(100, function ($pages) {
                foreach ($pages as $page) {
                    if (StoreNavigationPage::where('store_id', $this->toStoreId)->where('page_type',$page->page_type)->where('url_path',$page->url_path)->exists()) {
                        Log::info("Store Navigation Page with Page Type: " . $page->page_type . " and Page ID: " . $page->page_id . " already exists in Store ID: " . $this->toStoreId . ". Skipping duplication.");
                        continue;
                    }
                    Log::info("Processing Store Navigation Page ID: " . $page->id . " for Store ID: " . $this->fromStoreId);
                    $newPage = $page->replicate();
                    $newPage->store_id = $this->toStoreId;
                    $newPage->created_at = now();
                    $newPage->updated_at = now();
                    $newPage->save();
                    Log::info("New Store Navigation Page ID: " . $newPage->id . " for Store ID: " . $this->toStoreId);
                }
            });
    }
}
