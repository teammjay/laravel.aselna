<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
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

    public function generateFeedXML($listingId)
    {
        $listingInfo = ListingFeed::where('id', $listingId)->first();

        $userIdentifier = $listingInfo->store->user_id;

        $listingItems = ListingItemLink::where('feed_id', $listingId)->cursor();
// create a walmart feed
        $feed = [
            'xmlns' => 'http://walmart.com/',
            'MPItemFeedHeader' => [
                'version' => '3.1',
                'requestId' => 'd05aafd1-4fe7-d258-437c-e32c3e935e2e',
                'requestBatchId' => 'd05aafd1-4fe7-d258-437c-e32c3e935e2e_1',
                'feedDate' => '2022-04-08T21:06:36',
                'mart' => 'WALMART_US',
            ],
            'MPItem' => []
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [];
            $product['processMode'] = 'REPLACE_ALL';
            $product['feedDate'] = $listingItem->created_at;
            $product['sku'] = $item->sku;
            $product['productIdentifiers'] = [
                'productIdentifier' => [
                    'productIdType' => 'UPC',
                    'productId' => $item->upc,
                ]
            ];
            $product['MPProduct'] = [
                'SkuUpdate' => 'No',
                'productName' => $item->title,
                'ProductIdUpdate' => 'No',
            ];
            // ctageory is left
            $product['MPOffer'] = [
                'price' => 24,
                'MinimumAdvertisedPrice' => 54,
                'MustShipAlone' => 'No',
                'ShippingWeight' => [
                    'value' => 0.33,
                    'unit' => 'LB'
                ],
                'ProductTaxCode' => '2038716',
                'shipsInOriginalPackaging' => 'Yes'
            ];

            $feed['MPItem'][] = $product;
        }

        $xmlContent = XmlUtility::toXml($feed, 'MPItemFeed');

        XmlUtility::saveToFile('feeds/' . $listingId . '.xml', $xmlContent);

        //return the file path so that it can be downloaded
        return 'feeds/' . $listingId . '.xml';
    }

    public function submitFeed($feedId, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new WalmartAuthService($store->seller_id, $store->secret_key);

        $response = $authService->submitFeed();
//        $resultFeedId = $response['feedId'];

        // save the result and then check the process in queue after like 5-10 minutes
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

        $jsonFeed = [
            'MPItemFeedHeader' => [
                'sellingChannel' => 'marketplace',
                'mart' => 'WALMART_US',
                'subset' => 'EXTERNAL',
                'version' => '4.6',
                'local' => 'en',
                'subCategory' => 'electronics_other',
                'processMode' => 'REPLACE_ALL',
                'requestId' => $listingId,
                'requestBatchId' => $listingId . '_1',
            ]
        ];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $storeTypeCategory = $item->storeProduct->storeTypeCategory;
            $productObj = $item->storeProduct->product;
            $productColor = $productObj->productColor;

            $product = [
                'Orderable' => [
                    'endDate' => '2060-01-01T08:00:00Z',
                    'productName' => $item->title,
                    'productIdentifiers' => [
                        'productId' => $item->upc,
                        'productIdType' => 'UPC'
                    ],
                    'electronicsIndicator' => 'Yes',
                    'shipsInOriginalPackaging' => 'Yes',
                    'price' => '',
                    'sku' => $item->sku,
                    'brand' => $listingInfo->store->name,
                    'startDate' => '2019-01-01T08:00:00Z',
                    'MustShipAlone' => 'No',
                    'ShippingWeight' => ''
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

            $product['Visible'][$storeTypeCategory->name] = [
                'shortDescription' => $item->description,
                'mainImageUrl' => '',
                'productSecondaryImageURL' => '',
                'keyFeatures' => $keyFeatures,
                'manufacturerPartNumber' => $item->npm,
                'modelNumber' => $item->sku,
                'color' => $productColor->name,
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
}
