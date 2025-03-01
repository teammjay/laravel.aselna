<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Services\Auth\WalmartAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;

class WalmartService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();

        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::IMAGE_UPDATE:
                $this->generateImageUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->generateDeleteFeed($listingInfo, $listingId);
                break;
            case FeedTypes::INVENTORY:
                $this->generateInventoryFeed($listingInfo, $listingId);
                break;
            case FeedTypes::PRICE:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        return 'feeds/walmart/' . $listingId . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $file = public_path('feeds/walmart/' . $listingId . '.json');

        $response = $authService->submitFeed($file);
        Log::info('Response ::' . json_encode($response));

        $resultFeedId = $response['feedId'];

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        if ($feedInfo) {
            $feedInfo->feed_id = $resultFeedId;
            $feedInfo->save();
            Log::info("Feed ID updated successfully.");
        } else {
            Log::error("No ListingFeed found for listing_id: " . $listingId);
        }
    }

    public function getResultFeed(mixed $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        $response = $authService->getFeedStatus($feedInfo->feed_id);
        Log::info('Feed Status: ' . json_encode($response));

        // save response into folder
        FileUtility::saveToFile(public_path('result-feeds/walmart/' . $listingId . '.json'), json_encode($response));

        return 'result-feeds/walmart/' . $listingId . '.json';
    }

    private function generateCreateFeed($listingInfo, $listingId)
    {
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        $fileSystem = $listingInfo->store->fileSystem;

        $jsonFeed = [
            'MPItemFeedHeader' => [
                'subset' => 'EXTERNAL',
                'locale' => 'en',
                'sellingChannel' => 'marketplace',
                'processMode' => 'REPLACE',
                'version' => '4.8',
                'mart' => 'WALMART_US',
                'subCategory' => 'electronics_other',
                'requestId' => $listingId,
                'requestBatchId' => $listingId . '_1',
            ]
        ];

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
                $price = $productObj->product_price;
            }


            $product = [
                'Orderable' => [
                    'sku' => $item->sku,
                    'brand' => $listingInfo->store->name,
                    'productName' => $item->title,
                    'ShippingWeight' => intval($productObj->product_weight),
                    'productIdentifiers' => [
                        'productId' => $item->upc,
                        'productIdType' => 'UPC'
                    ],
                    'price' => $price,
                    'endDate' => '2060-01-01T08:00:00Z',
                    'electronicsIndicator' => 'Yes',
                    'shipsInOriginalPackaging' => 'Yes',
                    'startDate' => '2019-01-01T08:00:00Z',
                    'MustShipAlone' => 'No',
                ]
            ];

            $compatibleDevices = [];
            foreach ($item->devices as $device) {
                $compatibleDevices[] = $device->device->brand->name . ' ' . $device->device->name;
            }

            $keyFeatures = [];
            foreach ($productObj->productKeyFeatures as $feature) {
                $keyFeatures[] = $feature->value;
            }

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            Log::info('Images: ' . json_encode($images));
            Log::info('File System: ' . json_encode($fileSystem));
            $imagesPath = [];
            $mainImagePath = [];
            foreach ($images as $image) {
                if ($image->productImage && $image->productImage->main_image == 0) {
                    $imagesPath[] = $fileSystem->url_base_path . $image->path;
                } else {
                    $mainImagePath = $fileSystem->url_base_path . $image->path;
                }
            }

            // skip only the first one for the secondary image
            $product['Visible'][$storeTypeCategory->name] = [
                'shortDescription' => $item->description,
                'mainImageUrl' => $mainImagePath,
                'productSecondaryImageURL' => $imagesPath,
                'keyFeatures' => $keyFeatures,
                'manufacturerPartNumber' => $item->mpn,
                'modelNumber' => $item->sku,
                'msrp' => intval($productObj->product_mrsp),
                'color' => [$productColor->name ?? ''],
                'compatibleDevices' => $compatibleDevices,
            ];

            $jsonFeed['MPItem'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generateImageUpdateFeed($listingInfo, $listingId)
    {
    }

    private function generateDeleteFeed($listingInfo, $listingId)
    {
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RetireItemFeed xmlns="http://walmart.com/"></RetireItemFeed>');

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $skuList = $xml->addChild('SkuList', $item->sku);
        }

        $xml->asXML(public_path('feeds/walmart/' . $listingId . '.xml'));

    }

    private function generateInventoryFeed($listingInfo, $listingId)
    {
        //create afeed like this {"InventoryHeader":{"version":"1.4"},"Inventory":[{"sku":"Z41-LARWJD","quantity":{"unit":"EACH","amount":500}}]}
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [
            'InventoryHeader' => [
                'version' => '1.4'
            ],
            'Inventory' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [
                'sku' => $item->sku,
                'quantity' => [
                    'unit' => 'EACH',
                    'amount' => $item->quantity
                ]
            ];
            $jsonFeed['Inventory'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generatePriceFeed($listingInfo, $listingId)
    {
        // create a feed like this {"PriceHeader":{"version":"1.7"},"Price":[{"sku":"D93-JNRVJY","pricing":[{"currentPrice":{"currency":"USD","amount":11.99},"currentPriceType":"BASE"}]}]}

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [
            'PriceHeader' => [
                'version' => '1.7'
            ],
            'Price' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [
                'sku' => $item->sku,
                'pricing' => [
                    [
                        'currentPrice' => [
                            'currency' => 'USD',
                            'amount' => $item->price
                        ],
                        'currentPriceType' => 'BASE'
                    ]
                ]
            ];
            $jsonFeed['Price'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    public function getOrders()
    {
        $this->authService = new WalmartAuthService();
        $response = $this->authService->getOrders();

        return $response;
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }
}
