<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Auth\AmazonAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;

class AmazonService implements MarketplaceServiceInterface
{
    private $authService;

    public function __construct(AmazonAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function generateFeed($listingId)
    {
        $listingInfo = ListingFeed::where('id', $listingId)->first();
        // Generate feed for Amazon
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('feed_id', $listingId)->cursor();
        $feed = [
            'headers' => [
                'DocumentVersion' => '1.01',
                'MerchantIdentifier' => $userIdentifier,
            ],
            'MessageType' => 'Product',
        ];

        // Add products to the feed
        $messages = [];

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
//                'SearchTerms' => $item->search_terms,
//                'ItemType' => 'flat-sheets',
            ];
//            $product['ProductData'] = [
//                'HealthAndBeauty' => [
//                    'ProductType' => 'Health',
//                    'Beauty' => 'Beauty',
//                ],
//            ];

            $messages['MessageID'] = $listingItem->id;
            $messages['Product'] = $product;
        }


        $feed['Message'] = $messages;


        $xmlContent = XmlUtility::toXml($feed, 'AmazonEnvelope');

        XmlUtility::saveToFile('feeds/' . $listingId . '.xml', $xmlContent);

        //return the file path so that it can be downloaded
        return 'feeds/' . $listingId . '.xml';
    }

    public function submitFeed($feedId)
    {
        $content = '';
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId);
    }
}
