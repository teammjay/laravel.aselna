<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductImages;
use App\Models\SysFile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ListingItemImageCreate implements ShouldQueue
{
    use Queueable;

    private $listigId;

    /**
     * Create a new job instance.
     */
    public function __construct($listigId)
    {
        //
        $this->listigId = $listigId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listingInfo = Listing::find($this->listigId);
        Log::info('Listing :: ' . json_encode($listingInfo));
        ListingItemLink::where('listing_id', $this->listigId)->chunk(100, function ($listingItemLinks) use ($listingInfo) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemImage($listingItemLink, $listingInfo);
            }
        });
    }

    private function createListingItemImage(mixed $listingItemLink, $listingInfo)
    {
        //Log::info('ListingItemLink :: ' . json_encode($listingItemLink));
        $listingItem = $listingItemLink->item;
        $store = $listingInfo->store;
        $storeType = $listingInfo->store->storeType;
        $storeProduct = $listingItem->storeProduct;
        $productData = $storeProduct->product;
        //Log::info('ProductData :: ' . json_encode($productData));
        ProductImages::where('product_id', $productData->product_id)
            ->chunk(100, function ($productImages) use ($listingItem, $store, $storeType, $listingItemLink) {
                foreach ($productImages as $productImage) {
                    $Image = $productImage->imageObj;
                    $path = '';
                    Log::info('StoreType :: ' . json_encode($storeType));
                    switch (strtolower($storeType->name)) {
                        case 'walmart':
                            $path = '/store-images/' . $store->id . '/' . $listingItem->id . '_' . $productImage->id . '.jpg';
                            break;
                        case '';
                    }

                    ListingItemProductImage::updateOrCreate([
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                    ], [
                        'listing_item_id' => $listingItemLink->item_id,
                        'product_image_id' => $productImage->id,
                        'image_file_system_id' => $Image->id,
                        'path' => $path,
                        'reference_id' => '',
                    ]);
                }
            });
    }
}
