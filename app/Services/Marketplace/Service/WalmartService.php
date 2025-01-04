<?php

namespace App\Services\Marketplace\Service;

use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;

class WalmartService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = ListingFeed::where('id', $listingId)->first();

        $userIdentifier = $listingInfo->store->user_id;

        $listingItems = ListingItemLink::where('feed_id', $listingId)->cursor();

        // create a walmart feed
        //<MPItemFeed xmlns="http://walmart.com/">
        //    <MPItemFeedHeader>
        //        <version>3.1</version>
        //        <requestId>d05aafd1-4fe7-d258-437c-e32c3e935e2e</requestId>
        //        <requestBatchId>d05aafd1-4fe7-d258-437c-e32c3e935e2e_1</requestBatchId>
        //        <feedDate>2022-04-08T21:06:36</feedDate>
        //        <mart>WALMART_US</mart>
        //    </MPItemFeedHeader>
        //    <MPItem>
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

        // Add products to the feed

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $product = [];
            $product['SKU'] = $item->sku;
            $product['StandardProductID'] = [
                'Type' => 'UPC',
                'Value' => $item->upc,
            ];
            $product['Condition'] = [
                'ConditionType' => 'New'
            ];
            $product['DescriptionData'] = [
                'Title' => $item->title,
                'Brand' => $item->manufacturer->name,
                'PackageDimensions' => [
                    'Length' => '5',
                    'Width' => '5',
                    'Height' => '5',
                    'UnitOfMeasure' => 'IN',
                ],
                'ShippingWeight' => $item->description,
                'MerchantCatalogNumber' => $item->bullet_points,
                'MSRP' => $item->manufacturer,
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
