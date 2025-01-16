<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\ProductType;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Storage;

class GoogleShoppingService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        //$listingInfo = ListingFeed::where('id', $listingId)->first();

        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.device','listingItem.storeProduct','listingItem.storeProduct.product', 'listingItem.storeProduct.product.Manufacturer', 'listingItem.storeProduct.product.condition')->where('feed_id', $listingId)->limit(2)->get();

        // Define the columns for Google Shopping feed
        $columns = ['id','title','description','google product category','product type','link','image link','condition','availability','price','gtin','brand','mpn','item group id','gender','age group','color','size','shipping','additional_image_link','inventory','return_policy_info','custom_label_0','custom_label_1','custom_label_2','custom_label_3','custom_label_4','product_detail'];

        $fileName = 'google_shopping_feed_'.$listingId.'.csv';
        $filePath = 'feeds/google-shopping/' . $fileName;
        $stream = fopen('php://temp', 'w');

        // Write the header row
        fputcsv($stream, $columns);

        // Write product rows
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->listingItem;
            /*echo '<pre>';
            print_r($itemDetail);die;*/
            $ptype_id = $itemDetail->storeProduct->product->product_type;
            $typeDetail = ProductType::with('parent')->where('id',$ptype_id)->first();
            $product_type = $this->getCategoryPath($typeDetail);
            fputcsv($stream, [
                $itemDetail->id,
                $itemDetail->title,
                $itemDetail->description,
                $ptype_id,
                $product_type,
                '',
                '',
                $itemDetail->storeProduct->product->condition->name,
                'in stock',
                ($itemDetail->price != '') ? $itemDetail->price : $itemDetail->storeProduct->price,
                '',
                $itemDetail->storeProduct->product->Manufacturer->name,
                $itemDetail->mpn,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                $itemDetail->qty,
                '',
                (isset($itemDetail->listingItem->brand) && !empty($itemDetail->listingItem->brand)) ? $itemDetail->listingItem->brand->name : '',
                (isset($itemDetail->listingItem->device) && !empty($itemDetail->listingItem->device)) ? $itemDetail->listingItem->device->name : '',
                $itemDetail->storeProduct->product->product_title,
                '',
                $itemDetail->storeProduct->product->product_sku,
                ''
            ]);
        }
        rewind($stream);
        Storage::put($filePath, stream_get_contents($stream));
        fclose($stream);
        //return the file path so that it can be downloaded
        return $filePath;
    }

    private function getCategoryPath($category) {
        $path = [];
        while ($category) {
            $path[] = $category->name;
            $category = $category->parent;
        }
        return implode(' > ', array_reverse($path));
    }

    public function submitFeed($feedId, $listingId)
    {
        $content = '';
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId);
    }
}
