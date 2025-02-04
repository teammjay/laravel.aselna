<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingItemLink;
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
        ListingItemLink::where('listing_id', $this->listigId)->chunk(100, function ($listingItemLinks) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemImage($listingItemLink);
            }
        });
    }

    private function createListingItemImage(mixed $listingItemLink)
    {
        Log::info('ListingItemLink :: ' . json_encode($listingItemLink));
        $listingItem = $listingItemLink->item;
        $storeProduct = $listingItem->storeProduct;
        $productData = $storeProduct->product;
        ProductImages::where('product_id', $productData->id)
            ->chunk(100, function ($productImages) use ($listingItemLink) {
            foreach ($productImages as $productImage) {
                $Image = $productImage->image;
                Log::info('ProductImage :: ' . json_encode($productImage));
            }
        });
    }
}
