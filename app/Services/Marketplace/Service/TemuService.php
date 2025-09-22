<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use Illuminate\Support\Facades\Log;

class TemuService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();

        switch ($feedInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
//            case FeedTypes::IMAGE_UPDATE:
//                $this->generateImageUpdateFeed($listingInfo, $listingId);
//                break;
//            case FeedTypes::DELETE:
//                $this->generateDeleteFeed($listingInfo, $listingId);
//                break;
//            case FeedTypes::INVENTORY:
//                $this->generateInventoryFeed($listingInfo, $listingId, $feedInfo);
//                break;
//            case FeedTypes::SHIPMENT_UPDATE:
//                $this->callShipmentUpdateFeed($feedId);
//                break;
//            case FeedTypes::ORDER_CANCEL:
//                $this->callOrderCancelFeed($feedId);
//                break;
//            case FeedTypes::PRICE:
//                $this->generatePriceFeed($listingInfo, $listingId);
//                break;
            default:
                break;
        }

        return 'feeds/temu/' . $listingId . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        // TODO: Implement submitFeed() method.
    }

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
        // TODO: Implement getResultFeed() method.
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    private function generateCreateFeed($listingInfo, $listingId)
    {
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        $fileSystem = $listingInfo->store->fileSystem;

        $jsonFeed = [];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $storeTypeCategory = $item->storeProduct->storeTypeCategory;
            $productObj = $item->storeProduct->product;
            $productColor = $productObj->productColor;

            $price = $item->price;
            if (!isset($price)) {
                $price = $item->storeProduct->price;
            }
            if (!isset($price)) {
                $price = $productObj->product_marketplace_price;
            }

            $keyFeatures = [];
            foreach ($productObj->productKeyFeatures as $feature) {
                if ($feature->value == '' || !isset($feature->value)) continue;
                $keyFeatures[] = $feature->value;
            }

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            Log::info('Images: ' . json_encode($images));
            Log::info('File System: ' . json_encode($fileSystem));
            $imagesPath = [];
            $mainImagePath = [];
            foreach ($images as $image) {
                if ($image->productImage && $image->main_image == 0) {
                    $imagesPath[] = $fileSystem->url_base_path . $image->path;
                } else {
                    $mainImagePath = $fileSystem->url_base_path . $image->path;
                }
            }
            $jsonFeed['goodsBasic'] = [
                'goodsName' => $item->title,
                'catId' => $storeTypeCategory->category_id ?? '1000000',
            ];
            $jsonFeed['goodsDesc'] = '';
            $jsonFeed['skuList'] = [
                [
                    'skuId' => $item->sku,
                    'images' => $imagesPath,
                    'length' => 10,
                    'width' => 10,
                    'height' => 10,
                    'weight' => 0.5,
                    'weightUnit' => 'kg',
                    'volumeUnit' => round($price, 2),
                    'specIdList'

                ]
            ];

        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }
}
