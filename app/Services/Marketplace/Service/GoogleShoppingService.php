<?php

namespace App\Services\Marketplace\Service;

use App\Models\ListingItemLink;
use App\Models\ProductType;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Services\Auth\GoogleShoppingAuthService;
use Illuminate\Support\Facades\Storage;


class GoogleShoppingService implements MarketplaceServiceInterface
{
    protected $shoppingContentService;

    public function __construct(GoogleShoppingAuthService $shoppingContentService)
    {
        $this->shoppingContentService = $shoppingContentService;
    }

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        // Generate feed for Amazon
        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::SHIPMENT_UPDATE:
                $this->generateShipmentUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->generateDeleteFeed($listingInfo, $listingId);
                break;
            case FeedTypes::INVENTORY:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            case FeedTypes::PRICE:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        //return the file path so that it can be downloaded
        return 'feeds/google-shopping/' . $listingId . '.csv';
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.device','listingItem.storeProduct','listingItem.storeProduct.product', 'listingItem.storeProduct.productImages', 'listingItem.storeProduct.product.Manufacturer', 'listingItem.storeProduct.product.condition')->where('feed_id', $listingId)->cursor();

        // Define the columns for Google Shopping feed
        $columns = ['id','title','description','google product category','product type','link','image link','condition','availability','price','gtin','brand','mpn','item group id','gender','age group','color','size','shipping','additional_image_link','inventory','return_policy_info','custom_label_0','custom_label_1','custom_label_2','custom_label_3','custom_label_4','product_detail'];

        $fileName = 'google_shopping_feed_'.$listingId.'.csv';
        $filePath = public_path('feeds/google-shopping/' . $fileName);
        $stream = fopen('php://temp', 'w');

        // Write the header row
        fputcsv($stream, $columns);

        // Write product rows
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->listingItem;
            $images = array();
            if(isset($itemDetail->storeProduct->productImages) && !empty($itemDetail->storeProduct->productImages)){
                foreach($itemDetail->storeProduct->productImages as $image){
                    $images[] = asset('storage/product_images/'. $image->image);
                }
            }
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
                (!empty($images)) ? implode(',',$images) : '',
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

    public function generatePriceFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.device','listingItem.storeProduct','listingItem.storeProduct.product', 'listingItem.storeProduct.productImages', 'listingItem.storeProduct.product.Manufacturer', 'listingItem.storeProduct.product.condition')->where('feed_id', $listingId)->cursor();

        // Define the columns for Google Shopping feed
        $columns = ['product_id','price','currency'];

        $fileName = 'google_shopping_feed_'.$listingId.'.csv';
        $filePath = public_path('feeds/google-shopping/' . $fileName);
        $stream = fopen('php://temp', 'w');

        // Write the header row
        fputcsv($stream, $columns);

        // Write product rows
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->listingItem;
            fputcsv($stream, [
                $itemDetail->id,
                $itemDetail->price,
                'USD'
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

    public function submitFeed($feedId,$listingId)
    {
        // Upload the feed
        $fileName = 'google_shopping_feed_'.$listingId.'.csv';
        $filePath = public_path('feeds/google-shopping/' . $fileName);
        $response = $this->shoppingContentService->uploadFeed($filePath);

        if ($response['success']) {
            $statusResponse = $this->shoppingContentService->getFeedStatus($response['datafeedId']);



            return response()->json([
                'message' => $response['message'],
                'status' => $statusResponse,
            ]);
        }

        return response()->json([
            'message' => $response['message'],
            'errors' => $response['errors'],
        ], 400);

    }

    public function getResultFeed(mixed $listingId)
    {
        // TODO: Implement getResultFeed() method.

        $this->authService = new GoogleShoppingAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        $response = $this->authService->getFeedStatus($feedInfo->feed_id);

        return response()->json([
            'message' => $response['message'],
            'status' => $response,
        ]);

    }
}
