<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Auth\AmazonAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class AmazonService implements MarketplaceServiceInterface
{
    private $authService;

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        // Generate feed for Amazon
        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        //return the file path so that it can be downloaded
        return 'feeds/amazon/' . $listingId . '.xml';
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $feed = $xml->addChild('MPItemFeed');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.01');
        $header->addChild('MerchantIdentifier', $userIdentifier);

        $xml->addChild('MessageType', 'Product');
        $xml->addChild('PurgeAndReplace', 'false');

        $index = 1;

        foreach ($listingItems as $listingItem) {
            $messages = $xml->addChild('Message');
            $messages->addChild('MessageID', $index);
            $itemDetail = $listingItem->item;

            $product = $messages->addChild('Product');
            $product->addChild('SKU', $itemDetail->sku);
            $standardProductID = $product->addChild('StandardProductID');
            $standardProductID->addChild('Type', 'UPC');
            $standardProductID->addChild('Value', $itemDetail->upc);
            $product->addChild('ProductTaxCode', '');

            $condition = $product->addChild('Condition');
            $condition->addChild('ConditionType', 'New');

            $descriptionData = $product->addChild('DescriptionData');
            $safeText = htmlspecialchars($itemDetail->title, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $descriptionData->addChild('Title', $safeText);

            $safeText = htmlspecialchars($itemDetail->manufacturer->name, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $descriptionData->addChild('Brand', $safeText);

            $packageDimensions = $descriptionData->addChild('PackageDimensions');
            $packageDimensions->addChild('Length', '5');
            $packageDimensions->addChild('Width', '5');
            $packageDimensions->addChild('Height', '5');
            $packageDimensions->addChild('UnitOfMeasure', 'IN');


            $descriptionData->addChild('MSRP', $itemDetail->price);
            $descriptionData->addChild('Manufacturer', $itemDetail->manufacturer->name);

            $storeProducts = $itemDetail->storeProduct;
            if (isset($storeProducts)) {
                foreach ($storeProducts->features as $feature) {
                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                    $descriptionData->addChild('BulletPoint', $safeText);
                }
            }

//            $productData = $product->addChild('ProductData');
//            $wireless = $productData->addChild('wireless');
//            $productType = $wireless->addChild('ProductType');
//            $wirelessAccessories = $productType->addChild('WirelessAccessories');

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);
    }

    public function submitFeed($feedId, $listingId)
    {
        $content = '';
        $this->authService = new AmazonAuthService();
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId);
    }

    public function getResultFeed(mixed $listingId)
    {
        $this->authService = new AmazonAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        $response = $this->authService->getResultFeed($feedInfo->feed_id);

        FileUtility::saveToFile(public_path('result-feeds/amazon/' . $listingId . '.json'), json_encode($response));

        return 'result-feeds/amazon/' . $listingId . '.json';
    }
}
