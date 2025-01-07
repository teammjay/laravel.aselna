<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;

class WalmartService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        Log::info('Listing Info Created');
        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        $feedId = $feedInfo->id;
        Log::info('Feed Info Created' . $feedId);

//        $userIdentifier = $listingInfo->store->user_id;

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

            Log::info('Device: ' . json_encode($item->devices));

            $product['Visible'] = [];


            $jsonFeed['MPItem'][] = $product;
        }

        FileUtility::saveToFile('feeds/walmart/' . $listingId . '.json', json_encode($jsonFeed));

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

    public function submitFeed($feedId)
    {
        // TODO: Implement submitFeed() method.
    }
}
