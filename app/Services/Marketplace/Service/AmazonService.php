<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Auth\AmazonAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;

class AmazonService implements MarketplaceServiceInterface
{
    private $authService;

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        // Generate feed for Amazon
        $userIdentifier = $listingInfo->store->user_id;
        Log::info('Listing Info Created');
        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        $feedId = $feedInfo->id;
        Log::info('Feed Info Created ' . $feedId);

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

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
            $product['ProductTaxCode'] = '';
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
                'MSRP' => $item->price,
                'Manufacturer' => $item->manufacturer->name,
            ];
            $bulletPoints = [];
            $storeProducts = $item->storeProduct;
            foreach ($storeProducts->features as $feature) {
                $bulletPoints[] = $feature->value;
            }
            $product['DescriptionData']['BulletPoint'] = $bulletPoints;
            $devices = $item->devices;
            $product['ProductData']['wireless']['ProductType']['WirelessAccessories'] = [];

            $messages['MessageID'] = $listingItem->id;
            $messages['Product'] = $product;
        }


        $feed['Message'] = $messages;


        $xmlContent = XmlUtility::toXml($feed, 'AmazonEnvelope');

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);

        //return the file path so that it can be downloaded
        return 'feeds/amazon/' . $listingId . '.xml';
    }

    public function submitFeed($feedId, $listingId)
    {
        $content = '';
        $this->authService = new AmazonAuthService();
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId);
    }
}
