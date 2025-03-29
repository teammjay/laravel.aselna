<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductType;
use App\Models\SysFile;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Services\Auth\GoogleShoppingAuthService;
use Illuminate\Support\Facades\Storage;
use App\Constants\FeedTypes;
use App\Utils\FileUtility;


class GoogleShoppingService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        // Generate feed for Amazon
        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            /*case FeedTypes::SHIPMENT_UPDATE:
                $this->generateShipmentUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->generateDeleteFeed($listingInfo, $listingId);
                break;*/
            case FeedTypes::INVENTORY:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            case FeedTypes::PRICE:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        /* create listing feed */
        $filePath = public_path('feeds/google-shopping/' . $listingId . '.csv');

        //return the file path so that it can be downloaded
        return $filePath;
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.device', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.productImages', 'listingItem.storeProduct.product.Manufacturer', 'listingItem.storeProduct.product.condition', 'listingItem.storeProduct.StoreTypeCategory')->where('listing_id', $listingId)->cursor();

        $fileSystem = $listingInfo->store->fileSystem;

        // Define the columns for Google Shopping feed
        $columns = ['id', 'title', 'description', 'google product category', 'product type', 'link', 'image link', 'condition', 'availability', 'price', 'gtin', 'brand', 'mpn', 'item group id', 'gender', 'age group', 'color', 'size', 'shipping', 'additional_image_link', 'inventory', 'return_policy_info', 'custom_label_0', 'custom_label_1', 'custom_label_2', 'custom_label_3', 'custom_label_4', 'product_detail'];

        $fileName = 'google_shopping_feed_' . $listingId . '.csv';
        $filePath = public_path('feeds/google-shopping/' . $fileName);
        $stream = fopen('php://temp', 'w');

        // Write the header row
        fputcsv($stream, $columns);

        // Write product rows
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->listingItem;

            $images = array();
            $pimages = ListingItemProductImage::where('listing_item_id', $itemDetail->id)->get();
            foreach ($pimages as $image) {
                if ($image->productImage) {
                    $images[] = $fileSystem->url_base_path . $image->path;
                }
            }

            /*if(isset($itemDetail->storeProduct->productImages) && !empty($itemDetail->storeProduct->productImages)){
                foreach($itemDetail->storeProduct->productImages as $image){
                    $images[] = asset('storage/product_images/'. $image->image);
                }
            }*/
            /*echo '<pre>';
            print_r($itemDetail);die;*/
            $ptype_id = (isset($listingItem->listingItem->storeProduct->StoreTypeCategory) && !empty($listingItem->listingItem->storeProduct->StoreTypeCategory)) ? $listingItem->listingItem->storeProduct->StoreTypeCategory->store_type_code : '';
            $typeDetail = ProductType::with('parent')->where('id', $ptype_id)->first();
            $product_type = $this->getCategoryPath($typeDetail);
            fputcsv($stream, [
                $itemDetail->sku,
                $itemDetail->title,
                $itemDetail->description,
                $ptype_id,
                $product_type,
                '',
                (!empty($images)) ? implode(',', $images) : '',
                $itemDetail->storeProduct->product->condition->name,
                'in stock',
                ($itemDetail->price != '') ? $itemDetail->price : $itemDetail->storeProduct->price,
                '',
                $itemDetail->storeProduct->product->Manufacturer->name,
                $itemDetail->sku,
                '',
                '',
                '',
                '',
                '',
                'US::Standard:4.9 USD',
                '',
                $itemDetail->qty,
                'return_policy_days: "30"',
                (isset($itemDetail->listingItem->brand) && !empty($itemDetail->listingItem->brand)) ? $itemDetail->listingItem->brand->name : '',
                (isset($itemDetail->listingItem->device) && !empty($itemDetail->listingItem->device)) ? $itemDetail->listingItem->device->name : '',
                $itemDetail->storeProduct->product->product_title,
                '',
                $itemDetail->storeProduct->product->product_sku,
                ''
            ]);
        }
        rewind($stream);
        FileUtility::saveToFile(public_path('feeds/google-shopping/' . $listingId . '.csv'), stream_get_contents($stream));
        //Storage::put($filePath, stream_get_contents($stream));
        fclose($stream);
        //return the file path so that it can be downloaded
        return $filePath;
    }

    public function generatePriceFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        // Define the columns for Google Shopping feed
        $columns = ['product_id', 'price', 'currency'];

        $fileName = 'google_shopping_feed_' . $listingId . '.csv';
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

    private function getCategoryPath($category)
    {
        $path = [];
        while ($category) {
            $path[] = $category->name;
            $category = $category->parent;
        }
        return implode(' > ', array_reverse($path));
    }

    public function submitFeed($feedId, $listingId)
    {
        // Upload the feed
        $fileName = $listingId . '.csv';
        $filePath = public_path('feeds/google-shopping/' . $fileName);
        $authservice = new GoogleShoppingAuthService();
        $response = $authservice->uploadFeed($filePath);

        if ($response['success']) {
            $statusResponse = $authservice->getFeedStatus($response['datafeedId']);

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

    public function getResultFeed(mixed $listingId,$feedId = null)
    {
        $authService = new GoogleShoppingAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        $response = $authService->getFeedStatus($feedInfo->feed_id);

        return response()->json([
            'message' => $response['message'],
            'status' => $response,
        ]);

    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    public function getOrders()
    {
        $this->authService = new GoogleShoppingAuthService();
        $response = $this->authService->getOrders();

        return $response;
    }
}
