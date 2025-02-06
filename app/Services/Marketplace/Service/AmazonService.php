<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
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

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();

        $created_by = auth()->user()->name ?? 'admin';
        $feedData = array(
            'listing_id' => $listingId,
            // 'file_id' => $fileDetail->id,
            'status' => 0,
            'feed_type' => $listingInfo->feed_type,
            'store_id' => $listingInfo->store_id,
            'created_by' => $created_by
        );
        ListingFeed::updateOrCreate(['listing_id' => $listingId], $feedData);

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
        $userIdentifier = $listingInfo->store->user_id;
        Log::info('User Identifier: ' . json_encode($listingInfo->store));
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AmazonEnvelope>');

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
            $storeProduct = $itemDetail->storeProduct;
            $productData = $itemDetail->storeProduct->product;

            $price = $itemDetail->price;
            if (!isset($price)) {
                $price = $itemDetail->storeProduct->price;
            }
            if (!isset($price)) {
                $price = $productData->product_price;
            }

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

            $safeText = htmlspecialchars($productData->product_description, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $descriptionData->addChild('Description', $safeText);

            if (isset($itemDetail->manufacturer)) {
                $safeText = htmlspecialchars($itemDetail->manufacturer->name, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $descriptionData->addChild('Brand', $safeText);
            }

            $descriptionData->addChild('MfrPartNumber', $itemDetail->mpn);
            $productKeywords = ProductTypeKeyword::where('product_type_id', $productData->product_type)
                            ->limit(25) // Ensures only 30 records are fetched
                            ->get();

            $keywords = $productKeywords->pluck('keyword')->implode(' ');
            $descriptionData->addChild('SearchTerms', $keywords);

            if (isset($productData)) {
                $packageDimensions = $descriptionData->addChild('PackageDimensions');
                $packageDimensions->addChild('Length', $productData->package_dimensions_length);
                $packageDimensions->addChild('Width', $productData->package_dimensions_width);
                $packageDimensions->addChild('Height', $productData->package_dimensions_height);
                $packageDimensions->addChild('UnitOfMeasure', 'IN');
            }

            $descriptionData->addChild('MSRP', $itemDetail->price);
            if (isset($itemDetail->manufacturer)) {
                $descriptionData->addChild('Manufacturer', $itemDetail->manufacturer->name);
            }

            if (isset($productData)) {
                foreach ($storeProduct->features as $feature) {
                    $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                    if (!empty(trim($safeText))) {
                        $descriptionData->addChild('BulletPoint', $safeText);
                    }
                }

                $categoryId = $storeProduct->store_type_category_id;
                $tempCategoryId = $categoryId;
                $parentCategory = null;
                $subCategory = null;
                $currentCategory = StoreTypeCategory::where('id', $categoryId)->first();

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
                $CompatiblePhoneModels = rtrim($CompatiblePhoneModels, ', ');
                $subCategoryElement->addChild('CompatiblePhoneModels', $CompatiblePhoneModels);

                //combine 5 keywords and add into one element. do this for all the keywords
                $productKeywords = ProductTypeKeyword::where('product_type_id', $productData->product_type)
                                    ->limit(30) // Ensures only 30 records are fetched
                                    ->get();
                $counter = 0;
                $keywords = '';
                foreach ($productKeywords as $productKeyword) {
                    $counter++;
                    $keywords = $keywords . $productKeyword->keyword . ' ';
                    if ($counter % 6 == 0) {
                        $keywords = rtrim($keywords, ' ');
                        $subCategoryElement->addChild('Keywords', $keywords);
                        $keywords = '';
                    }
                }
                // ProductTypeKeyword::where('product_type_id', $productData->product_type)->chunk(6, function ($productKeywords) use ($subCategoryElement) {
                //     $keywords = '';
                //     foreach ($productKeywords as $productKeyword) {
                //         $keywords = $keywords . $productKeyword->keyword . ' ';
                //     }
                //     $keywords = rtrim($keywords, ' ');
                //     $subCategoryElement->addChild('Keywords', $keywords);
                // });

                $descriptionData->addChild('ItemType', $currentCategory->name);

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
        Log::info("Generated Feed ID :: " . $generatedFeedId);

        $feedResponse = $this->authService->submitFeed($generatedFeedId, $listingId);

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        if ($feedInfo && isset($feedResponse)) {
            $feedInfo->feed_id = $feedResponse['feedId'];
            $feedInfo->save();
            Log::info("Feed ID updated successfully.");
        } else {
            Log::error("No ListingFeed found for listing_id: " . $listingId);
        }
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
