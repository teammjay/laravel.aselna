<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductSearchTerm;
use App\Models\ProductTypeKeyword;
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

    public function generateFeed($listingId, $feedId = null)
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

        //return the file path so that it can be downloaded
        return 'feeds/amazon/' . $listingId . '.xml';
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        $fileSystem = $listingInfo->store->fileSystem;
        $userIdentifier = $listingInfo->store->user_id;
        Log::info('User Identifier: ' . json_encode($listingInfo->store));
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        // Build JSON feed structure
        $feed = [
            'header' => [
                'sellerId' => $userIdentifier,
                'version' => '2.0',
                'issueLocale' => 'en_US'
            ],
            'messages' => []
        ];

        $index = 1;

        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->item;
            $storeProduct = $itemDetail->storeProduct;
            $productData = $itemDetail->storeProduct->product;

            // Build message structure
            $message = [
                'messageId' => $index,
                'sku' => $itemDetail->sku,
                'operationType' => 'UPDATE',
                'requirements' => 'LISTING',
                'attributes' => [
                    'brand' => [['value' => $storeProduct->store->brand_name ?? '', 'language_tag' => 'en_US']],
                    'country_of_origin' => [['value' => 'CN']],
                    'item_name' => [[
                        'value' => $itemDetail->title,
                        'language_tag' => 'en_US',
                        'marketplace_id' => 'ATVPDKIKX0DER'
                    ]],
                    'product_description' => [['value' => $productData->product_description, 'language_tag' => 'en_US']],
                    'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
                    'parentage_level' => [['value' => 'parent']],
                    'batteries_required' => [['value' => false]],
                    'variation_theme' => [['name' => 'SIZE']],
                    'externally_assigned_product_identifier' => [[
                        'value' => $itemDetail->upc,
                        'type' => 'UPC'
                    ]],
                    'part_number' => [['value' => $itemDetail->mpn]],
                    'product_tax_code' => [['value' => 2040274]],
                ]
            ];
            if (isset($productData)) {
                $categoryId = $storeProduct->store_type_category_id;
                $currentCategory = StoreTypeCategory::where('id', $categoryId)->first();

                if ($currentCategory) {
                    $message['productType'] = $currentCategory->marketplace_code;
                    $message['attributes']['item_type_keyword'] = [['value' => $currentCategory->name]];
                }

                $bulletPoints = [];
                foreach ($storeProduct->features as $feature) {
                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    if (!empty(trim($safeText))) {
                        $bulletPoints[] = ['value' => $safeText, 'language_tag' => 'en_US'];
                    }
                }
                $message['attributes']['bullet_point'] = $bulletPoints;
                $message['attributes']['list_price'] = [['value' => number_format($itemDetail->price, 2), 'currency' => 'USD']];

                $message['attributes']['item_package_dimensions'] = [[
                    'length' => ['value' => $productData->package_dimensions_length, 'unit' => 'inches'],
                    'width' => ['value' => $productData->package_dimensions_width, 'unit' => 'inches'],
                    'height' => ['value' => $productData->package_dimensions_height, 'unit' => 'inches']
                ]];

                if (isset($storeProduct->store)) {
                    $message['attributes']['manufacturer'] = [['value' => $storeProduct->store->brand_name, 'language_tag' => 'en_US']];
                }

                $images = ListingItemProductImage::where('listing_item_id', $itemDetail->id)->get();
                $other_image_count = 1;
                foreach ($images as $image) {
                    if ($image->productImage && $image->productImage->main_image == 0) {
                        $message['attributes']['main_product_image_locator'] = [[
                            'media_location' => $fileSystem->url_base_path . $image->path
                        ]];
                    } else {
                        if ($other_image_count <= 8) {
                            $message['attributes']['other_product_image_locator_'. ($other_image_count++)]  = [[
                                'media_location' => $fileSystem->url_base_path . $image->path
                            ]];
                        }
                    }
                }
            }

            $feed['messages'][] = $message;

            $index++;
        }

        FileUtility::saveToFile(
            public_path('feeds/amazon/' . $listingId . '.json'),
            json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public function generateCreateFeedOld($listingInfo, $listingId)
    {
        $userIdentifier = $listingInfo->store->user_id;
        Log::info('User Identifier: ' . json_encode($listingInfo->store));
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        // Build JSON feed structure
        $feed = [
            'header' => [
                'sellerId' => $userIdentifier,
                'version' => '2.0',
                'issueLocale' => 'en_US'
            ],
            'messages' => []
        ];

        $index = 1;

        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->item;
            $storeProduct = $itemDetail->storeProduct;
            $productData = $itemDetail->storeProduct->product;

            // Build message structure
            $message = [
                'messageId' => $index,
                'sku' => $itemDetail->sku,
                'operationType' => 'UPDATE',
                'requirements' => 'LISTING',
                'attributes' => [
//                    'item_name' => [[
//                        'value' => $itemDetail->title,
//                        'language_tag' => 'en_US',
//                        'marketplace_id' => 'ATVPDKIKX0DER'
//                    ]],
//                    'condition_type' => [['value' => 'New']],
//                    'brand' => [['value' => $storeProduct->store->brand_name ?? '', 'language_tag' => 'en_US']],
//                    'product_description' => [['value' => $productData->product_description, 'language_tag' => 'en_US']],
//                    'part_number' => [['value' => $itemDetail->mpn]],
//                    // Required Amazon attributes
//                    'product_tax_code' => [['value' => 2040274]],
//                    'number_of_items' => [['value' => 1]],
//                    'included_components' => [['value' => 'Dashboard Mount', 'language_tag' => 'en_US']],
//                    'item_package_weight' => [['value' => 1, 'unit' => 'pounds']],
//                    'batteries_required' => [['value' => false]],
//                    // 'number_of_boxes' => [['value' => 1]],
//                    'merchant_suggested_asin' => [['value' => 'B0C5X3K7Q2']],
//                    'warranty_description' => [['value' => '2 Year Manufacturer', 'language_tag' => 'en_US']],
//                    'country_of_origin' => [['value' => 'China']],
//                    'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
//                    'material' => [['value' => 'Plastic']],
//                    'externally_assigned_product_identifier' => [[[
//                        'value' => $itemDetail->upc,
//                        'type' => 'UPC'
//                    ]]],
                ]
            ];

            // Add pricing information
//            $price = $itemDetail->price ?: $itemDetail->storeProduct->price ?: $productData->product_price;

//            if (isset($productData)) {
//                $message['attributes']['item_package_dimensions'] = [[
//                    'length' => ['value' => $productData->package_dimensions_length, 'unit' => 'inches'],
//                    'width' => ['value' => $productData->package_dimensions_width, 'unit' => 'inches'],
//                    'height' => ['value' => $productData->package_dimensions_height, 'unit' => 'inches']
//                ]];
//            }

//            $message['attributes']['list_price'] = [['value' => number_format($itemDetail->price, 2), 'currency' => 'USD']];
//            if (isset($storeProduct->store)) {
//                $message['attributes']['manufacturer'] = [['value' => $storeProduct->store->brand_name, 'language_tag' => 'en_US']];
//            }

            if (isset($productData)) {
//                $bulletPoints = [];
//                foreach ($storeProduct->features as $feature) {
//                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8');
//                    if (!empty(trim($safeText))) {
//                        $bulletPoints[] = ['value' => $safeText, 'language_tag' => 'en_US'];
//                    }
//                }
//                $message['attributes']['bullet_point'] = $bulletPoints;
//
//                // Build category hierarchy
//                $categoryId = $storeProduct->store_type_category_id;
//                $tempCategoryId = $categoryId;
//                $parentCategory = null;
//                $subCategory = null;
//                $currentCategory = StoreTypeCategory::where('id', $categoryId)->first();
//
//                while ($parentCategory = StoreTypeCategory::where('id', $tempCategoryId)->first()) {
//                    if (isset($parentCategory->parent_id)) {
//                        $tempCategoryId = $parentCategory->parent_id;
//                        $subCategory = $parentCategory;
//                    } else {
//                        break;
//                    }
//                }

//                if ($currentCategory) {
//                    $message['productType'] = 'PORTABLE_ELECTRONIC_DEVICE_MOUNT';
//                    $message['attributes']['item_type_keyword'] = [['value' => $currentCategory->name]];
//                }

//                $listingItemDetails = ListingItemDetail::where('listing_item_id', $itemDetail->id)->get();
//                $CompatiblePhoneModels = '';
//                foreach ($listingItemDetails as $listingItemDetail) {
//                    $device = $listingItemDetail->device;
//                    $deviceBrand = $device->brand;
//                    $CompatiblePhoneModels = $CompatiblePhoneModels . $deviceBrand->name . ' ' . $device->name . ', ';
//                }
//                $CompatiblePhoneModels = rtrim($CompatiblePhoneModels, ', ');

//                $message['attributes']['compatible_phone_models'] = [['value' => $CompatiblePhoneModels]];

                //combine 5 keywords and add into one element. do this for all the keywords
//                $productKeywords = ProductTypeKeyword::where('product_type_id', $productData->product_type)
//                    ->limit(30)
//                    ->get()
//                    ->pluck('keyword');

            }

            $feed['messages'][] = $message;

            $index++;
        }

        FileUtility::saveToFile(
            public_path('feeds/amazon/' . $listingId . '.json'),
            json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
    }

    public function submitFeed($feedId, $listingId)
    {
        Log::info("Started :: ");
        $filePath = public_path('feeds/amazon/' . $listingId . '.json');

        $content = file_get_contents($filePath);
        Log::info("Content :: " . $content);

        $this->authService = new AmazonAuthService();
        $generatedFeedId = $this->authService->uploadProductFeed($content);
        Log::info("Generated Feed ID :: " . $generatedFeedId);

        $feedResponse = $this->authService->submitFeed($generatedFeedId, $listingId);

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        if ($feedInfo && $feedResponse) {
            $feedInfo->feed_id = $feedResponse['feedId'];
            $feedInfo->save();
            Log::info("Feed ID updated successfully.");
        } else {
            Log::error("No ListingFeed found for listing_id: " . $listingId);
        }
    }

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
        $this->authService = new AmazonAuthService();

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        return $this->authService->getResultFeed($feedInfo->feed_id, $listingId);
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


    public function getOrders()
    {
        $this->authService = new AmazonAuthService();
        $response = $this->authService->getOrders();

        return $response;
    }

    public function getOrderItems(mixed $orderId)
    {
        $this->authService = new AmazonAuthService();
        $response = $this->authService->getOrderItems($orderId);

        return $response;
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }
}
