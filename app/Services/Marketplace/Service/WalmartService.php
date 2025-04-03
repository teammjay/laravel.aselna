<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Constants\ListingSyncStatus;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Services\Auth\WalmartAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;
use App\Constants\FeedStatus;

class WalmartService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId,$feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();

        switch ($feedInfo->feed_type) {
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
                $this->generateInventoryFeed($listingInfo, $listingId,$feedInfo);
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
        $feedInfo = ListingFeed::where('id', $feedId)->first();
        $store = $listingInfo->store;

        $feedType = $this->getfeedType($feedInfo->feed_type);

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
        $file = public_path('feeds/walmart/' . $listingId . '.json');

        $response = $authService->submitFeed($file,$feedType);
        Log::info('Response ::' . json_encode($response));

        $resultFeedId = $response['feedId'];

        $feedInfo = ListingFeed::where('id', $feedId)->first();

        if ($feedInfo) {
            $feedInfo->feed_id = $resultFeedId;
            $feedInfo->submit_ts = now();
            $feedInfo->number_of_attempts = $feedInfo->number_of_attempts ?? 0 + 1;
            $feedInfo->save();
            Log::info("Feed ID updated successfully.");

        } else {
            Log::error("No ListingFeed found for listing_id: " . $listingId);
        }
    }

    public function submitInventoryFeedAfterCreate($listingId,$listingInfo) {
        Log::info('Submit Inventory Feed After Create');
        $created_by = auth()->user()->name ?? 'admin';
        Log::info('Created By: ' . $created_by);

        $feedData = array(
            'listing_id' => $listingId,
            'status' => 0,
            'feed_type' => FeedTypes::INVENTORY,
            'store_id' => $listingInfo->store_id,
            'created_by' => $created_by
        );
        Log::info('Feed Data: ' . json_encode($feedData));

        $newfeed =  ListingFeed::updateOrCreate([
            'listing_id' => $listingId,
            'feed_type' => FeedTypes::INVENTORY
        ], $feedData);

        $this->generateInventoryFeed($listingInfo, $listingId,$newfeed);
        Log::info('Inventory Feed Generated: ' . json_encode($newfeed));

        $this->submitFeed($newfeed->id, $listingId);
        Log::info('Inventory Feed Submitted: ' . json_encode($newfeed));
    }

    public function getResultFeed(mixed $listingId,$feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $feedInfo = ListingFeed::where('id', $feedId)->first();

        $response = $authService->getFeedStatus($feedInfo->feed_id);
        Log::info('Feed Status: ' . json_encode($response));

        if ($response['feedStatus'] != 'PROCESSED') return null;

        $feedInfo->update([
            'success_count' => $response['itemsSucceeded'],
            'fail_count' => $response['itemsFailed'],
            'processing_count' => $response['itemsProcessing'],
            'number_of_items' => $response['itemsReceived'],
        ]);

        if ($feedInfo->feed_type == FeedTypes::CREATE) {
            foreach ($response['itemDetails']['itemIngestionStatus'] as $item) {
                $listingItems = ListingItem::where('sku', $item['sku'])->get();
                foreach($listingItems as $listingItem) {
                    if($item['ingestionStatus'] && isset($item['itemid'])) {
                        $listingItem->update([
                            'store_sku' => $item['itemid']
                        ]);
                    } else {

                    }
                    
                }
            }

        }   

        if($response['feedStatus'] == 'PROCESSED' && intval($response['itemsSucceeded']) > 0 
                && $feedInfo->feed_type == FeedTypes::CREATE) {
            $this->submitInventoryFeedAfterCreate($listingId,$listingInfo);
        }

        // save response into folder
        FileUtility::saveToFile(public_path('result-feeds/walmart/' . $feedInfo->id . '.json'), json_encode($response));

        $feedInfo-> update([
            'status' => FeedStatus::PROCESSED
        ]);

        /* counting based on store sku*/
        $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
        if (count($listing_items ?? []) > 0) {
            $successCnt = $failCnt = 0;
            foreach ($listing_items as $item) {

                $listingItem = ListingItem::findOrFail($item->item_id);
                if($listingItem->store_sku != ''){
                    $listingItem->update([
                        'sync_status' => ListingSyncStatus::Synchronized
                    ]);
                    $successCnt++;
                }else{
                    $listingItem->update([
                        'sync_status' => ListingSyncStatus::Unsynchronize
                    ]);
                    $failCnt++;
                }
            }
            $feedInfo->update([
                'success_count' => $successCnt,
                'fail_count' => $failCnt
            ]);
        }
        /* counting based on store sku*/

        return 'result-feeds/walmart/' . $feedInfo->id . '.json';
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
                $price = $productObj->product_marketplace_price;
            }


            $product = [
                'Orderable' => [
                    'sku' => $item->sku,
                    'brand' => $listingInfo->store->name,
                    'productName' => $item->title,
                    'ShippingWeight' => floatval($productObj->product_weight),
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
                if($feature->value == '' || !isset($feature->value)) continue;
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
                'manufacturerPartNumber' => '' . $item->sku,
                'modelNumber' => $item->sku,
                'msrp' => floatval($productObj->product_mrsp),
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

        $jsonFeed = [
            'RetireItemHeader' => [
                'version' => '1.0'
            ],
            'RetireItem' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;

            $product = [
                'sku' => $item->sku
            ];
            $jsonFeed['RetireItem'][] = $product;
        }

        FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generateInventoryFeed($listingInfo, $listingId, $feedInfo)
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

        Log::info('Listing Feed Type: ' . $feedInfo->feed_type);
        if($feedInfo->feed_type == FeedTypes::CREATE) {
            FileUtility::saveToFile(public_path('feeds/walmart/I_' . $listingId . '.json'), json_encode($jsonFeed));
        } else {
            FileUtility::saveToFile(public_path('feeds/walmart/' . $listingId . '.json'), json_encode($jsonFeed));
        }
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
        //$this->authService = new WalmartAuthService();
        //$response = $this->authService->getOrders();

        // return $response;
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    public function getfeedType($feedType)
    {
        switch ($feedType) {
            case FeedTypes::CREATE:
                return 'MP_ITEM';
            // case FeedTypes::IMAGE_UPDATE:
            //     return 'MPItem';
            case FeedTypes::DELETE:
                return 'RETIRE_ITEM';
            case FeedTypes::INVENTORY:
                return 'inventory';
            case FeedTypes::PRICE:
                return 'price';
            default:
                return '';
        }
    }
}
