<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;

class GoogleShoppingService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        //$listingInfo = ListingFeed::where('id', $listingId)->first();

        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product')->where('feed_id', $listingId)->limit(2)->get();

        // Define the columns for Google Shopping feed
        $columns = ['id','title','description','google product category','product type','link','image link','condition','availability','price','gtin','brand','mpn','item group id','gender','age group','color','size','shipping','additional_image_link','inventory','return_policy_info','custom_label_0','custom_label_1','custom_label_2','custom_label_3','custom_label_4','product_detail'];

        $fileName = 'google_shopping_feed_'.$listingId.'.csv';
        $filePath = 'feeds/google-shopping/' . $fileName;

        $file = fopen(storage_path('public/' . $filePath), 'w');

        // Write the header row
        fputcsv($file, $columns);

        // Write product rows
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->item;
            fputcsv($file, [
                $itemDetail->id,
                $itemDetail->title,
                $itemDetail->description,
                $itemDetail->price,
                $itemDetail->link,
                $itemDetail->image_link,
                $itemDetail->availability,
            ]);
        }

        fclose($file);
        //return the file path so that it can be downloaded
        return $filePath;
    }

    public function submitFeed($feedId)
    {
        $content = '';
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId);
    }
}
