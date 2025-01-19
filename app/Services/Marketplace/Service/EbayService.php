<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Services\Auth\EbayAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;

class EbayService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        //$listingInfo = ListingFeed::where('id', $listingId)->first();

        // Generate feed for Ebay
        //$userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product','listingItem.storeProduct.productImages')->where('feed_id', $listingId)->limit(2)->get();
        $version = 1;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        foreach ($listingItems as $key=>$listingItem) {
            $itemDetail = $listingItem->item;

            // Add AddFixedPriceItemRequest
            $addFixedPriceItemRequest = $xml->addChild('AddFixedPriceItemRequest', null, 'urn:ebay:apis:eBLBaseComponents');
            $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
            $addFixedPriceItemRequest->addChild('Version', $version);
            $addFixedPriceItemRequest->addChild('WarningLevel', 'High');

            // Add Item details
            $item = $addFixedPriceItemRequest->addChild('Item');
            $item->addChild('CategoryMappingAllowed', 'true');
            $item->addChild('Country', 'US');
            $item->addChild('Currency', 'USD');
            $item->addChild('Description', $itemDetail->description);
            $item->addChild('ListingDuration', 'GTC');
            $item->addChild('ListingType', 'FixedPriceItem');
            $item->addChild('Location', '06473');

            // Add PrimaryCategory
            $primaryCategory = $item->addChild('PrimaryCategory');
            $primaryCategory->addChild('CategoryID', (isset($itemDetail->storeProduct->product) && !empty($itemDetail->storeProduct->product)) ? $itemDetail->storeProduct->product->product_type : '');

            // Add ProductListingDetails
            if(isset($itemDetail->listingItem->brand) && !empty($itemDetail->listingItem->brand)) {
                $productListingDetails = $item->addChild('ProductListingDetails');
                $brandMPN = $productListingDetails->addChild('BrandMPN');
                $brandMPN->addChild('Brand', $itemDetail->listingItem->brand->name);
                $brandMPN->addChild('MPN', $itemDetail->mpn);
            }

            // Add ShippingDetails
            $shippingDetails = $item->addChild('ShippingDetails');
            $shippingServiceOptions = $shippingDetails->addChild('ShippingServiceOptions');
            $shippingServiceOptions->addChild('ShippingService', 'ShippingMethodStandard');
            $shippingServiceOptions->addChild('ShippingServiceCost', '0.0')->addAttribute('currencyID', 'USD');
            $shippingServiceOptions->addChild('ShippingServiceAdditionalCost', '0.0')->addAttribute('currencyID', 'USD');
            $shippingServiceOptions->addChild('ShippingServicePriority', '1');
            $shippingServiceOptions->addChild('FreeShipping', 'true');
            $shippingDetails->addChild('ShippingType', 'Flat');

            $item->addChild('Site', 'US');
            $item->addChild('Title', $itemDetail->title);
            $item->addChild('SKU', $itemDetail->sku);

            // Add PictureDetails
            $pictureDetails = $item->addChild('PictureDetails');
            if(isset($itemDetail->storeProduct->productImages) && !empty($itemDetail->storeProduct->productImages)){
                foreach($itemDetail->storeProduct->productImages as $image){
                    $pictureDetails->addChild('PictureURL', asset('storage/product_images/'. $image->image));
                }
            }

            $item->addChild('DispatchTimeMax', '1');

            // Add ItemSpecifics
            if(isset($itemDetail->listingItem->brand) && !empty($itemDetail->listingItem->brand)) {
                $itemSpecifics = $item->addChild('ItemSpecifics');
                $specifics = [
                    ['Type', 'CEA Bundle'],
                    ['Fits Model', $itemDetail->listingItem->brand->name.' '.$itemDetail->listingItem->device->name],
                    ['Brand', $itemDetail->listingItem->brand->name],
                    ['MPN', $itemDetail->mpn],
                    ['Model', $itemDetail->mpn],
                ];
                foreach ($specifics as $specific) {
                    $nameValueList = $itemSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', $specific[0]);
                    $nameValueList->addChild('Value', $specific[1]);
                }
            }


            // Add ReturnPolicy
            $returnPolicy = $item->addChild('ReturnPolicy');
            $returnPolicy->addChild('RefundOption', 'MoneyBack');
            $returnPolicy->addChild('ReturnsAcceptedOption', 'ReturnsAccepted');
            $returnPolicy->addChild('ShippingCostPaidByOption', 'Seller');

            // Add InventoryTrackingMethod
            $item->addChild('InventoryTrackingMethod', 'SKU');

            // Add Variations
            $variations = $item->addChild('Variations');
            $variationData = ListingItemDetail::with('device','device.brand')->where('listing_item_id',$itemDetail->id)->get();

            $compatibleModels = [];
            if(!empty((array) $variationData)) {
                foreach ($variationData as $variant) {
                    $variation = $variations->addChild('Variation');
                    $variation->addChild('SKU', $variant->sku);
                    $variation->addChild('StartPrice', $variant->price);
                    $variation->addChild('Quantity', $variant->qty);
                    $variationSpecifics = $variation->addChild('VariationSpecifics');
                    $nameValueList = $variationSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', 'Compatible Model');
                    $nameValueList->addChild('Value', $variant->device->name);
                    $variationTitle = $variant->title;
                    $variation->addChild('VariationTitle', $variationTitle);
                    $variationDetails = $variation->addChild('VariationProductListingDetails');
                    $variationDetails->addChild('UPC', $variant->upc);
                    $compatibleModels[] = $variant->device->brand->name.' '.$variant->device->name;
                }
            }

            // Add VariationSpecificsSet
            $variationSpecificsSet = $variations->addChild('VariationSpecificsSet');
            $nameValueList = $variationSpecificsSet->addChild('NameValueList');
            $nameValueList->addChild('Name', 'Compatible Model');
            foreach ($compatibleModels as $model) {
                $nameValueList->addChild('Value', $model);
            }

            $item->addChild('ConditionID', '1000');
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile('feeds/ebay/ebay-' . $listingId . '.xml', $xmlContent);

        //return the file path so that it can be downloaded
        return 'feeds/ebay/' . $listingId . '.xml';
    }

    public function submitFeed($feedId,$listingId)
    {
        $authService = new EbayAuthService();

        $fileName = 'ebay-'.$listingId.'.xml';
        $filePath = 'feeds/ebay/' . $fileName;

        $taskID = $authService->createTask();
        $response = $authService->uploadFile($taskID, $fileName, $filePath);
        if($response){
            $updateFeedData = [
                'feed_id' => $response['feedId'],
                'feed_type' => $response['feedType']
            ];

            ListingFeed::update($updateFeedData,['id'=>$feedId]);
        }


    }

    public function getResultFeed(mixed $listingId)
    {
        // TODO: Implement getResultFeed() method.
    }
}
