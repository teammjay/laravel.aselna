<?php

namespace App\Services\Marketplace\Service;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\SysFile;
use App\Services\Auth\EbayAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\XmlUtility;
use App\Constants\FeedTypes;
use Illuminate\Support\Facades\Log;

class EbayService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        /*echo '<pre>';
        print_r($listingInfo);
        echo '</pre>';die;*/

        // Generate feed for Amazon
        switch ($listingInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::SHIPMENT_UPDATE:
                $this->generateShipmentUpdateFeed($listingInfo, $listingId);
                break;
            case FeedTypes::DELETE:
                $this->generateDeleteFeed($listingInfo, $listingId);
                break;
            case FeedTypes::INVENTORY:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            case FeedTypes::PRICE:
                $this->generatePriceFeed($listingInfo, $listingId);
                break;
            default:
                break;
        }

        // create entry for file
        $filePath = public_path('feeds/ebay/' . $listingId . '.xml');
        /* create listing feed */
        return $filePath;
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product','listingItem.storeProduct.productImages')->where('listing_id', $listingId)->get();
        $fileSystem = $listingInfo->store->fileSystem;
        $version = 1240;

        /*echo '<pre>';
        print_r($listingItems);
        echo '</pre>';die;*/

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
            //$addFixedPriceItemRequest->addChild('WarningLevel', 'High');

            // Add Item details
            $item = $addFixedPriceItemRequest->addChild('Item');
            //$item->addChild('CategoryMappingAllowed', 'true');
            $item->addChild('Country', 'US');
            $item->addChild('Currency', 'USD');
            $item->addChild('Description', htmlspecialchars($itemDetail->description));
            $item->addChild('ListingDuration', 'GTC');
            $item->addChild('ListingType', 'FixedPriceItem');
            $item->addChild('Location', '06473');

            // Add PrimaryCategory
            $primaryCategory = $item->addChild('PrimaryCategory');
            $primaryCategory->addChild('CategoryID', (isset($listingItem->listingItem->storeProduct->StoreTypeCategory) && !empty($listingItem->listingItem->storeProduct->StoreTypeCategory)) ? $listingItem->listingItem->storeProduct->StoreTypeCategory->store_type_code : '');

            // Add ProductListingDetails
            if(isset($listingItem->listingItem->storeProduct->store) && !empty($listingItem->listingItem->storeProduct->store)) {
                $productListingDetails = $item->addChild('ProductListingDetails');
                $brandMPN = $productListingDetails->addChild('BrandMPN');
                $brandMPN->addChild('Brand', $listingItem->listingItem->storeProduct->store->name);
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
            $images = ListingItemProductImage::where('listing_item_id', $itemDetail->id)->get();
            foreach ($images as $image) {
                if ($image->productImage) {
                    $imagesPath = $fileSystem->url_base_path . $image->path;
                    $pictureDetails->addChild('PictureURL', $imagesPath);
                }
            }
            /*if(isset($itemDetail->storeProduct->productImages) && !empty($itemDetail->storeProduct->productImages)){
                foreach($itemDetail->storeProduct->productImages as $image){
                    $pictureDetails->addChild('PictureURL', asset('storage/product_images/'. $image->image));
                }
            }*/

            $item->addChild('DispatchTimeMax', '1');

            // Add ItemSpecifics
            if(isset($listingItem->listingItem->storeProduct->StoreTypeCategory) && !empty($listingItem->listingItem->storeProduct->StoreTypeCategory)) {
                $itemSpecifics = $item->addChild('ItemSpecifics');
                $specifics = [
                    ['Type', $listingItem->listingItem->storeProduct->StoreTypeCategory->name],
                    ['Fits Model', (isset($listingItem->listingItem->brand)) ? $listingItem->listingItem->brand->name.' '.$listingItem->listingItem->device->name : ''],
                    ['Brand', $listingItem->listingItem->brand->name],
                    ['MPN', $itemDetail->mpn],
                    ['Model', $itemDetail->mpn],
                ];
                foreach ($specifics as $specific) {
                    $nameValueList = $itemSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', $specific[0]);
                    $nameValueList->addChild('Value', htmlspecialchars($specific[1]));
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
                    $variation->addChild('StartPrice', $itemDetail->price);
                    $variation->addChild('Quantity', $itemDetail->quantity);
                    $variationSpecifics = $variation->addChild('VariationSpecifics');
                    $nameValueList = $variationSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', 'Compatible Model');
                    $nameValueList->addChild('Value', $variant->device->brand->name.' '.$variant->device->name);
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

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);
    }

    public function generatePriceFeed($listingInfo, $listingId){

        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product','listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = 1;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        $index = 1;
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;

            // Add ReviseInventoryStatusRequest
            $addFixedPriceItemRequest = $xml->addChild('ReviseInventoryStatusRequest', null, 'urn:ebay:apis:eBLBaseComponents');
            $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
            $addFixedPriceItemRequest->addChild('Version', $version);
            $addFixedPriceItemRequest->addChild('WarningLevel', 'High');

            $inventoryStatus = $xml->addChild('InventoryStatus');
            $inventoryStatus->addChild('SKU', $itemDetail->sku);
            $inventoryStatus->addChild('StartPrice', $itemDetail->price)->addAttribute('currencyID', 'USD');;
            $inventoryStatus->addChild('Quantity', $itemDetail->quantity);

            $index ++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);

    }

    public function generateDeleteFeed($listingInfo, $listingId){

        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product','listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = 1;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        $index = 1;
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;
            // Add ReviseInventoryStatusRequest
            $addFixedPriceItemRequest = $xml->addChild('EndFixedPriceItemRequest', null, 'urn:ebay:apis:eBLBaseComponents');
            $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
            $addFixedPriceItemRequest->addChild('Version', $version);
            $addFixedPriceItemRequest->addChild('WarningLevel', 'High');
            $addFixedPriceItemRequest->addChild('EndingReason', 'NotAvailable');
            $addFixedPriceItemRequest->addChild('SKU', $itemDetail->sku);
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);

    }

    public function generateShipmentUpdateFeed($listingInfo, $listingId){

        $listingItems = ListingItemLink::with('listingItem','listingItem.brand','listingItem.storeProduct','listingItem.storeProduct.product','listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = 1;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        $index = 1;
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;
            // Add ReviseInventoryStatusRequest
            $addFixedPriceItemRequest = $xml->addChild('SetShipmentTrackingInfoRequest');
            $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
            $addFixedPriceItemRequest->addChild('Version', $version);
            $addFixedPriceItemRequest->addChild('WarningLevel', 'High');
            $addFixedPriceItemRequest->addChild('OrderID', 'NotAvailable');

            $shipment = $xml->addChild('Shipment');
            $shipment->addChild('ShippingCarrierUsed', 'USPS');
            $shipment->addChild('ShippingServiceUsed', 'First Class');
            $shipment->addChild('ShipmentTrackingNumber', '9400111899561627596553');
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);
    }

    public function submitFeed($feedId,$listingId)
    {
        $authService = new EbayAuthService();

        $fileName = $listingId.'.xml';
        $filePath = public_path('feeds/ebay/' . $fileName);
        //echo $filePath;die;
        Log::info('Feed Id : '.$feedId);
        $listingDetail = ListingFeed::where('listing_id',$listingId)->first();
        $feed_id = $listingDetail->id;

        $taskID = $authService->createTask();

        ListingFeed::findOrFail($feed_id)->update([ 'feed_id' => $taskID]);

        $response = $authService->uploadFile($taskID, $fileName, $filePath);
        Log::info('Feed Submitted : '.$feedId);
        //$this->downloadResultFeed($taskID, $listingId);
        /*if(isset($response['fileUploadStatus']) && $response['fileUploadStatus'] == 'SUCCESS'){
            $updateFeedData = [
                'feed_id' => $taskID,
                'feed_type' => $response['feedType']
            ];

            ListingFeed::update($updateFeedData,['id'=>$feedId]);

            return response()->json([
                'message' => $response['feedId'],
                'status' => $response['fileUploadStatus'],
            ]);
        }*/
        return response()->json([
            'message' => 'success'
        ], 200);
    }

    public function getResultFeed(mixed $listingId)
    {
        $this->authService = new EbayAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        if($feedInfo){
            $response = $this->authService->getResultFeed($feedInfo->feed_id,$listingId);
            //$response = $this->authService->getResultFeed('task-1-1275361',$listingId);
            return $response;
        }

        return false;
    }

    public function downloadResultFeed($taskID, $listingId)
    {
        $this->authService = new EbayAuthService();

        //$feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        $response = $this->authService->getResultFeed('task-1-1274321',$listingId);

        return $response;

    }

    public function getInputFeed($taskID, $listingId)
    {
        $this->authService = new EbayAuthService();

        $response = $this->authService->downloadInputFeed($taskID,$listingId);

        return $response;

    }

    public function getOrders()
    {
        $this->authService = new EbayAuthService();

        $response = $this->authService->getOrders();

        return $response;

    }
}
