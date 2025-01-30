<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\StoreTypeCategory;
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

        $created_by = auth()->user()->name ?? 'admin';
        $feedData = array(
            'listing_id' => $listingId,
            // 'file_id' => $fileDetail->id,
            'status' => 0,
            'feed_type' => $listingInfo->feed_type,
            'store_id' => $listingInfo->store_id,
            'created_by' => $created_by
        );
        ListingFeed::updateOrCreate(['listing_id' => $listingId],$feedData);

        //return the file path so that it can be downloaded
        return 'feeds/amazon/' . $listingId . '.xml';
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        Log::info('User Identifier: ' . json_encode($listingInfo->store));
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $feed = $xml->addChild('MPItemFeed');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.1');
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

            $product->addChild('ProductTaxCode', '2040274');

            $condition = $product->addChild('Condition');
            $condition->addChild('ConditionType', 'New');

            if (isset($itemDetail->upc)) {
                $standardProductID = $product->addChild('StandardProductID');
                $standardProductID->addChild('Type', 'UPC');
                $standardProductID->addChild('Value', $itemDetail->upc);
            }

            $descriptionData = $product->addChild('DescriptionData');
            $safeText = htmlspecialchars($itemDetail->title, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $descriptionData->addChild('Title', $safeText);

            if(isset($itemDetail->manufacturer)) {
                $safeText = htmlspecialchars($itemDetail->manufacturer->name, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $descriptionData->addChild('Brand', $safeText);
            }

            $productData = $itemDetail->storeProduct->product;

            $packageDimensions = $descriptionData->addChild('PackageDimensions');
            $packageDimensions->addChild('Length', $productData->package_dimensions_length);
            $packageDimensions->addChild('Width', $productData->package_dimensions_width);
            $packageDimensions->addChild('Height', $productData->package_dimensions_height);
            $packageDimensions->addChild('UnitOfMeasure', 'IN');


            $descriptionData->addChild('MSRP', $itemDetail->price);
            if(isset($itemDetail->manufacturer)) {
                $descriptionData->addChild('Manufacturer', $itemDetail->manufacturer->name);
            }

            $storeProducts = $itemDetail->storeProduct;
            if (isset($storeProducts)) {
                foreach ($storeProducts->features as $feature) {
                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                    $descriptionData->addChild('BulletPoint', $safeText);
                }

                $categoryId = $storeProducts->store_type_category_id;
                $tempCategoryId = $categoryId;
                $parentCategory = null;
                $subCategory = null;

                while ($parentCategory = StoreTypeCategory::where('id', $tempCategoryId)->first()) {
                    if (isset($parentCategory->parent_id)) {
                        $tempCategoryId = $parentCategory->parent_id;
                        $subCategory = $parentCategory;
                    } else {
                        break;
                    }
                }

                $productDataElement = $product->addChild('ProductData');
                $parentCategoryElement = $productDataElement->addChild($parentCategory->name);
                $productType = $parentCategoryElement->addChild('ProductType');
                $subCategoryElement = $productType->addChild($subCategory->name);

                $listingItemDetails = ListingItemDetail::where('listing_item_id', $itemDetail->id)->get();
                $CompatiblePhoneModels = '';
                foreach ($listingItemDetails as $listingItemDetail) {
                    $device = $listingItemDetail->device;
                    $deviceBrand = $device->brand;
                    $CompatiblePhoneModels = $CompatiblePhoneModels . $deviceBrand->name . ' ' . $device->name . ', ';
                }
                $CompatiblePhoneModels = trim($CompatiblePhoneModels, ',');
                $subCategoryElement->addChild('CompatiblePhoneModels', $CompatiblePhoneModels);

            }

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);
    }

    public function submitFeed($feedId, $listingId)
    {
        Log::info("Started :: ");
        $filePath = public_path('feeds/amazon/' . $listingId . '.xml');

        $content = file_get_contents($filePath);
        Log::info("Content :: " . $content);

        $this->authService = new AmazonAuthService();
        $generatedFeedId = $this->authService->uploadProductFeed($content);

        $this->authService->submitFeed($generatedFeedId,$listingId);
    }

    public function getResultFeed(mixed $listingId)
    {
        $this->authService = new AmazonAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        $response = $this->authService->getResultFeed($feedInfo->feed_id);

        FileUtility::saveToFile(public_path('result-feeds/amazon/' . $listingId . '.json'), json_encode($response));

        return 'result-feeds/amazon/' . $listingId . '.json';
    }

    private function generateImageUpdateFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.01');
        $header->addChild('MerchantIdentifier', $userIdentifier);

        $xml->addChild('MessageType', 'ProductImage');

        $index = 1;
        foreach ($listingItems as $listingItem) {
            $messages = $xml->addChild('Message');
            $messages->addChild('MessageID', $index);
            $itemDetail = $listingItem->item;

            $productImage = $messages->addChild('ProductImage');
            $productImage->addChild('SKU', $itemDetail->sku);
            $productImage->addChild('ImageType', 'Main');
//            $productImage->addChild('ImageLocation', $itemDetail->image_url);

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);
    }

    private function generateDeleteFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.01');
        $header->addChild('MerchantIdentifier', $userIdentifier);

        $xml->addChild('MessageType', 'Product');

        $index = 1;
        foreach ($listingItems as $listingItem) {
            $messages = $xml->addChild('Message');
            $messages->addChild('MessageID', $index);
            $messages->addChild('OperationType', 'Delete');
            $itemDetail = $listingItem->item;

            $product = $messages->addChild('Product');
            $product->addChild('SKU', $itemDetail->sku);

            $index++;
        }
    }

    private function generateInventoryFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.01');
        $header->addChild('MerchantIdentifier', $userIdentifier);

        $xml->addChild('MessageType', 'Inventory');

        $index = 1;
        foreach ($listingItems as $listingItem) {
            $messages = $xml->addChild('Message');
            $messages->addChild('MessageID', $index);
            $itemDetail = $listingItem->item;

            $inventory = $messages->addChild('Inventory');
            $inventory->addChild('SKU', $itemDetail->sku);
            $inventory->addChild('Quantity', $itemDetail->quantity);

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);
    }

    private function generatePriceFeed($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

        $header = $xml->addChild('Header');
        $header->addChild('DocumentVersion', '1.01');
        $header->addChild('MerchantIdentifier', $userIdentifier);

        $xml->addChild('MessageType', 'Price');

        $index = 1;
        foreach ($listingItems as $listingItem) {
            $messages = $xml->addChild('Message');
            $messages->addChild('MessageID', $index);
            $itemDetail = $listingItem->item;

            $price = $messages->addChild('Price');
            $price->addChild('SKU', $itemDetail->sku);
            $standardPrice = $price->addChild('StandardPrice');
            // add current like attribute not element
            $standardPrice->addAttribute('currency', 'USD');
            $standardPrice->addChild('Amount', $itemDetail->price);

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/amazon/' . $listingId . '.xml'), $xmlContent);
    }


}
