<?php

namespace App\Services\Marketplace\Service;

use App\Constants\OrderStatus;
use App\Models\Order;
use DateTime;
use App\Constants\FeedStatus;
use App\Constants\ListingSyncStatus;
use App\Constants\OrderSyncronisationType;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemDetail;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ListingTemplate;
use App\Models\OrderSynchronization;
use App\Models\ProductImageAlternative;
use App\Models\ProductKeyFeatures;
use App\Models\ProductSpecification;
use App\Models\ProductTitleKeyword;
use App\Models\StorePage;
use App\Models\StoreProduct;
use App\Models\StoreType;
use App\Models\ListingItem;
use App\Models\SysFile;
use App\Services\Auth\EbayAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\ListingUtility;
use App\Utils\OrderUtility;
use App\Utils\Spintax;
use App\Utils\XmlUtility;
use App\Constants\FeedTypes;
use Illuminate\Support\Facades\Log;

class EbayService implements MarketplaceServiceInterface
{

    private $version = '1250';

    public function generateFeed($listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        /*echo '<pre>';
        print_r($listingInfo);
        echo '</pre>';die;*/

        // Generate feed for eBay
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
        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.devices', 'listingItem.devices.device', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.product.color', 'listingItem.storeProduct.store', 'listingItem.storeProduct.StoreTypeCategory', 'listingItem.storeProduct.productImages')->where('listing_id', $listingId)->get();
        $fileSystem = $listingInfo->store->fileSystem;
        $version = $this->version;

        /*echo '<pre>';
        print_r($listingItems);
        echo '</pre>';die;*/

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        foreach ($listingItems as $key => $listingItem) {
            $itemDetail = $listingItem->item;

            /*echo '<pre>';
            print_r($itemDetail);
            echo '</pre>';die;*/
            $description = htmlspecialchars($itemDetail->description);
            $store_id = $listingItem->listingItem->storeProduct->store->id;
            $listing_template_id = $listingItem->listingItem->storeProduct->store->listing_template_id;
            $product = $listingItem->listingItem->storeProduct->product;
            if ($listing_template_id != '') {
                $listing_template = ListingTemplate::findOrFail($listing_template_id)->toArray();
                /*echo '<pre>';
                print_r($listing_template['code']);
                echo '</pre>';die;*/
                $template = $listing_template['code'];

                $productTitle = $itemDetail->title;
                $store = $listingItem->listingItem->storeProduct->store;
                $descriptionParagraphs = explode(PHP_EOL, $description);
                $keyFeatures = ProductKeyFeatures::where('product_id', $product->product_id)->orderBy('sort_order')->get();
                $shippingText = StorePage::getStorePageByPageType($store_id, 2);
                $returnsPage = StorePage::with('PageType')->where('store_id', $store_id)->where('page_type', '3')->first();
                $returnsPageContent = \Blade::render($returnsPage->text, compact('store'));
                $returnsText = explode(PHP_EOL, $returnsPageContent);
                $paymentText = StorePage::getStorePageByPageType($store_id, 4);
                $feedbackText = StorePage::getStorePageByPageType($store_id, 8);
                $returnPolicyText = StorePage::getStorePageByPageType($store_id, 10);
                $images = ListingItemProductImage::where('listing_item_id', $itemDetail->id)->get();
                $storeDetails = [
                    'logoUrl' => '',
                    'feedbackUrl' => '',
                    'storeUrl' => '',
                    'contactUsUrl' => '',
                    'aboutUsUrl' => '',
                    'newsletterUrl' => '',
                    'copyrightText' => ''
                ];
                $pimages = [];
                foreach ($images as $image) {
                    if ($image->productImage) {
                        $imagesPath = $fileSystem->url_base_path . $image->path;
                        $pimages[] = $imagesPath;
                    }
                }

                $html = \Blade::render($template, compact('store', 'productTitle', 'keyFeatures', 'shippingText', 'paymentText', 'feedbackText', 'returnPolicyText', 'returnsText', 'descriptionParagraphs', 'pimages', 'product', 'storeDetails'));
                $description = preg_replace('/\s+/', ' ', trim($html));
            }

            $store_type_detail = StoreType::getStoreTypeDetailById($store_id);
            $max_title_length = $store_type_detail->max_title_length;

            $storeProduct = StoreProduct::where('product_id', $product->product_id)->where('store_id', $store_id)->first();
            //$variationtitle = ListingUtility::generateTitle($storeProduct, true, $max_title_length);

            $shortkeywords = ProductTitleKeyword::select('keyword')->where('product_id', $product->product_id)->orderBy('priority', 'asc')->limit(3)->get()->toArray();
            $longkeywords = ProductTitleKeyword::select('keyword')->where('product_id', $product->product_id)->orderBy('priority', 'asc')->offset(3)->limit(2)->get()->toArray();


            ///echo $description;die;

            // Add AddFixedPriceItemRequest
            if (isset($itemDetail->store_sku) && $itemDetail->store_sku != '') {
                $addFixedPriceItemRequest = $xml->addChild('ReviseFixedPriceItemRequest', null, 'urn:ebay:apis:eBLBaseComponents');
            } else {
                $addFixedPriceItemRequest = $xml->addChild('AddFixedPriceItemRequest', null, 'urn:ebay:apis:eBLBaseComponents');
            }
            $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
            $addFixedPriceItemRequest->addChild('Version', $version);
            //$addFixedPriceItemRequest->addChild('WarningLevel', 'High');

            // Add Item details
            $item = $addFixedPriceItemRequest->addChild('Item');
            if (isset($itemDetail->store_sku) && $itemDetail->store_sku != '') {
                $item->addChild('ItemID', $itemDetail->store_sku);
            }
            //$item->addChild('CategoryMappingAllowed', 'true');
            $item->addChild('Country', 'US');
            $item->addChild('Currency', 'USD');
            $item->addChild('Description', $description);
            $item->addChild('ListingDuration', 'GTC');
            $item->addChild('ListingType', 'FixedPriceItem');
            $item->addChild('Location', '06473');

            Log::info('Listing Item Id : ' . json_encode($itemDetail->id));
            Log::info('Listing Item StoreProduct : ' . json_encode($itemDetail->storeProduct));
            Log::info('Listing Item StoreCategory : ' . json_encode($itemDetail->storeProduct->StoreTypeCategory));
            Log::info('Listing Item Id : ' . json_encode($listingItem->listingItem));
            // Add PrimaryCategory
            $primaryCategory = $item->addChild('PrimaryCategory');
            $primaryCategory->addChild('CategoryID', (isset($listingItem->listingItem->storeProduct->StoreTypeCategory) && !empty($listingItem->listingItem->storeProduct->StoreTypeCategory)) ? $listingItem->listingItem->storeProduct->StoreTypeCategory->store_type_code : '');

            // Add ProductListingDetails
            if (isset($listingItem->listingItem->storeProduct->store) && !empty($listingItem->listingItem->storeProduct->store)) {
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
            $item->addChild('Title', htmlspecialchars($itemDetail->title));
            $item->addChild('SKU', $itemDetail->sku);

            // Add PictureDetails
            $pictureDetails = $item->addChild('PictureDetails');
            $images = ListingItemProductImage::leftJoin('product_images', 'product_images.id', '=', 'listing_item_product_images.product_image_id')->where('listing_item_product_images.listing_item_id', $itemDetail->id)->orderBy('listing_item_product_images.main_image', 'desc')->get();
            foreach ($images as $image) {
                if ($image->productImage) {
                    if (strpos($image->path, 'ebayimg') !== false) {
                        $imagesPath = $image->path;
                    } else {
                        $imagesPath = $fileSystem->url_base_path . $image->path;
                    }
                    /*$filename = basename($imagesPath,".jpg");
                    $clientId = env('EBAY_CLIENT_ID');
                    $clientSecret = env('EBAY_CLIENT_SECRET');
                    $redirectUri = env('EBAY_REDIRECT_URI');

                    $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri,$store->user_id);
                    $epsUrl = $authService->uploadImageToEPS($imagesPath, $filename);

                    if ($epsUrl) {
                        $hostimage = $epsUrl;
                    } else {
                        // Log or handle the failure per image
                        $hostimage = $imagesPath;
                    }*/
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
            if (isset($listingItem->listingItem->storeProduct->StoreTypeCategory) && !empty($listingItem->listingItem->storeProduct->StoreTypeCategory)) {
                $itemSpecifics = $item->addChild('ItemSpecifics');
                if (isset($listingItem->listingItem->devices) && count($listingItem->listingItem->devices ?? []) > 0) {
                    foreach ($listingItem->listingItem->devices as $k => $device) {
                        if ($k >= 30) {
                            break;
                        }
                        if (strtolower($listingItem->listingItem->brand->name) == 'apple' || strtolower($listingItem->listingItem->brand->name) == 'unlocked') {
                            $fitModel[] = (isset($listingItem->listingItem->brand)) ? $device->device->name : '';
                        } else {
                            $fitModel[] = (isset($listingItem->listingItem->brand)) ? $listingItem->listingItem->brand->name . ' ' . $device->device->name : '';
                        }
                    }
                } else {
                    if (strtolower($listingItem->listingItem->brand->name) == 'apple' || strtolower($listingItem->listingItem->brand->name) == 'unlocked') {
                        $fitModel[] = (isset($listingItem->listingItem->brand)) ? $listingItem->listingItem->device->name : '';
                    } else {
                        $fitModel[] = (isset($listingItem->listingItem->brand)) ? $listingItem->listingItem->brand->name . ' ' . $listingItem->listingItem->device->name : '';
                    }
                }
                sort($fitModel);
                $specifics = [
                    ['Type', $listingItem->listingItem->storeProduct->StoreTypeCategory->name],
                    /* ['Fits Model', $fitModel],*/
                    ['Brand', $listingItem->listingItem->storeProduct->store->name],
                    ['MPN', $itemDetail->mpn],
                    ['Model', $itemDetail->mpn],
                ];

                /*echo '<pre>';
                print_r($fitModel);
                echo '</pre>';die;*/

                foreach ($specifics as $specific) {
                    $nameValueList = $itemSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', htmlspecialchars($specific[0]));
                    if ($specific[0] == 'Fits Model') {
                        if (is_array($specific[1])) {
                            $prevModel = [];
                            foreach ($specific[1] as $fmodle) {
                                if (!in_array($fmodle, $prevModel)) {
                                    $nameValueList->addChild('Value', htmlspecialchars($fmodle));
                                }
                                $prevModel[] = $fmodle;
                            }
                        }
                    } else {
                        $nameValueList->addChild('Value', htmlspecialchars($specific[1]));
                    }
                }

                if (isset($listingItem->listingItem->storeProduct->product->color) && $listingItem->listingItem->storeProduct->product->color->name != '') {
                    //$specifics['Color'] = $listingItem->listingItem->storeProduct->product->color->name;
                    $nameValueList = $itemSpecifics->addChild('NameValueList');
                    $nameValueList->addChild('Name', 'Color');
                    $nameValueList->addChild('Value', $listingItem->listingItem->storeProduct->product->color->name);
                }

                $productSpecification = ProductSpecification::where('product_id', $product->product_id)->where('store_type_id', $store_type_detail->id)->get();
                if (count($productSpecification ?? []) > 0) {
                    foreach ($productSpecification as $specificaiton) {

                        $nameValueList = $itemSpecifics->addChild('NameValueList');
                        $nameValueList->addChild('Name', htmlspecialchars($specificaiton->name));
                        if (str_contains($specificaiton->value, ',')) {
                            if (strtolower($specificaiton->name) != 'type') {
                                $values = explode(',', $specificaiton->value);
                            } else {
                                $values = $specificaiton->value;
                            }
                            if (is_array($values)) {
                                foreach ($values as $val) {
                                    $nameValueList->addChild('Value', htmlspecialchars($val));
                                }
                            } else {
                                $nameValueList->addChild('Value', htmlspecialchars($values));
                            }
                        } else {
                            $nameValueList->addChild('Value', htmlspecialchars($specificaiton->value));
                        }
                    }
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

            $field = 'brands.id as brand_id';
            if ($listingInfo->include_carriers || $listingInfo->carrier_combo) {
                $field = 'carrier.name as carrier_name';
            }
            $variationDataQuery = ListingItemDetail::select('listing_item_detail.*', 'brands.name as brand_name', 'device.name as device_name', $field)->with('device', 'device.brand', 'carrier');
            $variationDataQuery->leftJoin('device', 'device.id', '=', 'listing_item_detail.device_id');
            $variationDataQuery->leftJoin('brands', 'brands.id', '=', 'device.brand_id');

            if ($listingInfo->include_carriers || $listingInfo->carrier_combo) {
                $variationDataQuery->leftJoin('carrier', 'carrier.id', '=', 'listing_item_detail.device_carrier_id');
            }
            $variationDataQuery->where('listing_item_id', $itemDetail->id);
            if ($listingInfo->include_carriers || $listingInfo->carrier_combo) {
                $variationDataQuery->orderBy('carrier.name', 'ASC');
            }
            $variationDataQuery->orderBy('brands.name', 'ASC');
            $variationDataQuery->orderBy('device.name', 'ASC');
            //$variationDataQuery->orderBy('listing_item_detail.created_at','ASC');
            $variationData = $variationDataQuery->get();

            /*echo '<pre>';
            print_r($variationData);
            echo '</pre>';die;*/
            $compatibleModels = $compatibleCarriers = [];
            if (!empty((array)$variationData)) {
                foreach ($variationData as $variant) {
                    $variation = $variations->addChild('Variation');
                    $variation->addChild('SKU', $variant->sku);
                    $variation->addChild('StartPrice', $itemDetail->price);
                    $variation->addChild('Quantity', $itemDetail->quantity);
                    $variationSpecifics = $variation->addChild('VariationSpecifics');
                    if (($listingInfo->carrier_combo) && isset($variant->carrier)) {
                        $nameValueList = $variationSpecifics->addChild('NameValueList');
                        $nameValueList->addChild('Name', 'Choose Your Carrier');
                        $nameValueList->addChild('Value', $variant->carrier->name);
                        $compatibleCarriers[] = $variant->carrier->name;
                    }
                    $nameValueList1 = $variationSpecifics->addChild('NameValueList');
                    $nameValueList1->addChild('Name', 'Compatible Model');
                    if (strtolower($variant->device->brand->name) == 'apple' || strtolower($variant->device->brand->name) == 'unlocked') {
                        if ($listingInfo->include_carriers && !$listingInfo->carrier_combo && isset($variant->carrier)) {
                            if (strtolower($variant->carrier->name) == 'unlocked') {
                                $deviceName = $variant->device->name;
                                $compatibleModels[] = $variant->device->name;
                            } else {
                                $deviceName = $variant->carrier->name . ' ' . $variant->device->name;
                                $compatibleModels[] = $variant->carrier->name . ' ' . $variant->device->name;
                            }
                        } else {
                            $deviceName = $variant->device->name;
                            $compatibleModels[] = $variant->device->brand->name . ' ' . $variant->device->name;
                        }
                        $deviceName = str_replace('"', '', $deviceName);
                        $nameValueList1->addChild('Value', htmlspecialchars($deviceName));
                        $variationTitle = $this->generateVariationTitle($deviceName, $shortkeywords, $longkeywords, $max_title_length);
                        //$variationTitle = $variationtitle.' '.$variant->device->name;

                    } else {
                        if ($listingInfo->include_carriers && !$listingInfo->carrier_combo && isset($variant->carrier)) {
                            if (strtolower($variant->carrier->name) == 'unlocked') {
                                $deviceName = $variant->device->brand->name . ' ' . $variant->device->name;
                            } else {
                                $deviceName = $variant->carrier->name . ' ' . $variant->device->brand->name . ' ' . $variant->device->name;
                            }
                        } else {
                            $deviceName = $variant->device->brand->name . ' ' . $variant->device->name;
                        }
                        $deviceName = str_replace('"', '', $deviceName);
                        $nameValueList1->addChild('Value', htmlspecialchars($deviceName));
                        $variationTitle = $this->generateVariationTitle($deviceName, $shortkeywords, $longkeywords, $max_title_length);
                        $compatibleModels[] = $deviceName;
                    }

                    $variation->addChild('VariationTitle', htmlspecialchars($variationTitle));
                    $variationDetails = $variation->addChild('VariationProductListingDetails');
                    $variationDetails->addChild('UPC', $itemDetail->upc);
                }
            }

            // Add VariationSpecificsSet
            $variationSpecificsSet = $variations->addChild('VariationSpecificsSet');
            if (($listingInfo->carrier_combo) && isset($variant->carrier)) {
                $nameValueList = $variationSpecificsSet->addChild('NameValueList');
                $nameValueList->addChild('Name', 'Choose Your Carrier');
                sort($compatibleCarriers);
                $lastvariant = null;
                foreach ($compatibleCarriers as $carrier) {
                    if ($lastvariant != $carrier) {
                        $nameValueList->addChild('Value', $carrier);
                    }
                    $lastvariant = $carrier;
                }
            }
            $nameValueList2 = $variationSpecificsSet->addChild('NameValueList');
            $nameValueList2->addChild('Name', 'Compatible Model');
            // sort the compatible models
            sort($compatibleModels);
            $lastmodel = null;
            foreach ($compatibleModels as $model) {
                $model = str_replace('"', '', $model);
                $model = htmlspecialchars($model);
                if ($lastmodel != $model) {
                    if (strpos($model, 'Apple') !== false) {
                        $newmodel = str_replace('Apple', '', $model);
                        $nameValueList2->addChild('Value', trim($newmodel));
                    } else if (strpos($model, 'UNLOCKED') !== false) {
                        $newmodel = str_replace('UNLOCKED', '', $model);
                        $nameValueList2->addChild('Value', trim($newmodel));
                    } else {
                        $nameValueList2->addChild('Value', trim($model));
                    }
                }
                $lastmodel = $model;
            }

            $item->addChild('ConditionID', '1000');
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);
    }

    private function generateSiteHostedImages($imagepath)
    {

    }

    private function generateVariationTitle($device_name, $short_keywords = [], $long_keywords = [], $max_length = 80)
    {
        $spintax = new Spintax();
        $title_template = "For #device_name - #short_keywords #long_keywords";

        $skeywords = $lkeywords = [];
        if (!empty($short_keywords)) {
            foreach ($short_keywords as $skey) {
                $skeywords[] = $skey['keyword'];
            }
        }
        if (!empty($long_keywords)) {
            foreach ($long_keywords as $lkey) {
                $lkeywords[] = $lkey['keyword'];
            }
        }
        $short_str = implode(' ', $skeywords);
        $short_str = $spintax->process($short_str);
        $long_str = implode(' ', $lkeywords);
        $long_str = $spintax->process($long_str);

        $title = str_replace(
            ['#device_name', '#short_keywords', '#long_keywords'],
            [$device_name, $short_str, $long_str],
            $title_template
        );

        /*if (strlen($title) > $max_length) {
            $title = str_replace($long_str, '', $title);
            $title = trim(preg_replace('/\s+/', ' ', $title));
        }

        if (strlen($title) > $max_length) {
            $title = substr($title, 0, $max_length - 3);
        }*/

        return $title;
    }

    public function generatePriceFeed($listingInfo, $listingId)
    {

        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = $this->version;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><BulkDataExchangeRequests xmlns="urn:ebay:apis:eBLBaseComponents"></BulkDataExchangeRequests>');

        // Add Header
        $header = $xml->addChild('Header');
        $header->addChild('SiteID', '0');
        $header->addChild('Version', $version);

        $index = 1;
        foreach ($listingItems as $listingItem) {

            $itemDetail = $listingItem->item;

            // $inventoryStatus = $addFixedPriceItemRequest->addChild('InventoryStatus');
            // if(isset($itemDetail->store_sku)) {
            //     $inventoryStatus->addChild('ItemID', $itemDetail->store_sku);
            // }
            // $inventoryStatus->addChild('SKU', $itemDetail->sku);
            // $inventoryStatus->addChild('StartPrice', $itemDetail->price)->addAttribute('currencyID', 'USD');;
            // $inventoryStatus->addChild('Quantity', $itemDetail->quantity);

            $variationData = ListingItemDetail::where('listing_item_id', $itemDetail->id)->orderBy('created_at', 'ASC')->get();
            Log::info('Variation Data : ' . json_encode($variationData));
            if (!empty((array)$variationData)) {
                foreach ($variationData as $variant) {
                    // Add ReviseInventoryStatusRequest
                    $addFixedPriceItemRequest = $xml->addChild('ReviseInventoryStatusRequest', null, 'urn:ebay:apis:eBLBaseComponents');
                    $addFixedPriceItemRequest->addChild('ErrorLanguage', 'en_US');
                    $addFixedPriceItemRequest->addChild('Version', $version);
                    //$addFixedPriceItemRequest->addChild('WarningLevel', 'High');
                    $inventoryStatus = $addFixedPriceItemRequest->addChild('InventoryStatus');
                    if (isset($itemDetail->store_sku)) {
                        $inventoryStatus->addChild('ItemID', $itemDetail->store_sku);
                    }
                    $inventoryStatus->addChild('SKU', $variant->sku);
                    $inventoryStatus->addChild('StartPrice', $itemDetail->price)->addAttribute('currencyID', 'USD');;
                    $inventoryStatus->addChild('Quantity', $itemDetail->quantity);
                }
            }

            $index++;
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);

    }

    public function generateDeleteFeed($listingInfo, $listingId)
    {

        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = $this->version;

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
            //$addFixedPriceItemRequest->addChild('WarningLevel', 'High');
            $addFixedPriceItemRequest->addChild('EndingReason', 'NotAvailable');
            $addFixedPriceItemRequest->addChild('SKU', $itemDetail->sku);
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);

    }

    public function generateShipmentUpdateFeed($listingInfo, $listingId)
    {

        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.productImages')->where('listing_id', $listingId)->cursor();
        $version = $this->version;

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
            //$addFixedPriceItemRequest->addChild('WarningLevel', 'High');
            $addFixedPriceItemRequest->addChild('OrderID', 'NotAvailable');

            $shipment = $addFixedPriceItemRequest->addChild('Shipment');
            $shipment->addChild('ShippingCarrierUsed', 'USPS');
            $shipment->addChild('ShippingServiceUsed', 'First Class');
            $shipment->addChild('ShipmentTrackingNumber', '9400111899561627596553');
        }

        $xmlContent = $xml->asXML();

        XmlUtility::saveToFile(public_path('feeds/ebay/' . $listingId . '.xml'), $xmlContent);
    }

    public function submitFeed($feedId, $listingId)
    {
        $version = $this->version;
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;
        /*echo '<pre>';
        print_r($store);
        echo '</pre>';die;*/
        $clientId = env('EBAY_CLIENT_ID');
        $clientSecret = env('EBAY_CLIENT_SECRET');
        $redirectUri = env('EBAY_REDIRECT_URI');

        $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);

        $fileName = $listingId . '.xml';
        $filePath = public_path('feeds/ebay/' . $fileName);
        //echo $filePath;die;
        Log::info('Feed Id : ' . $feedId);
        $listingDetail = ListingFeed::where('listing_id', $listingId)->first();
        $feed_id = $listingDetail->id;

        if ($listingInfo->feed_type == 5) {

        } else {

            $taskID = $authService->createTask($version, $listingInfo);

            $feedInfo = ListingFeed::findOrFail($feed_id);
            $feedInfo->update(['feed_id' => $taskID, 'submit_ts' => now(), 'number_of_attempts' => $feedInfo->number_of_attempts ?? 0 + 1]);

            Log::info('File Path : ' . $filePath);
            $response = $authService->uploadFile($taskID, $fileName, $filePath);
            Log::info('Feed Submitted : ' . $feedId);

        }
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

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $clientId = env('EBAY_CLIENT_ID');
        $clientSecret = env('EBAY_CLIENT_SECRET');
        $redirectUri = env('EBAY_REDIRECT_URI');

        $authService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);

        $feedInfo = ListingFeed::where('listing_id', $listingId)->first();
        Log::info('Feed Response :: ' . json_encode($feedInfo));

        if ($feedInfo) {
            Log::info('Feed Id : ' . $feedInfo->feed_id);
            $response = $authService->getResultFeed($feedInfo->feed_id, $listingId);
            Log::info('Feed Response :: ' . json_encode($response));
            if ($response) {
                $zipPath = public_path($response);
                $extractPath = public_path('result-feeds/ebay/' . $listingId);
                Log::info("get_current_user() : " . get_current_user());
                Log::info("extract Path : " . $extractPath);

                if (!file_exists($extractPath)) {
                    mkdir($extractPath, 0775, true);
                    chown($extractPath, 'aselna');
                    chgrp($extractPath, 'psacln');
                    chown($zipPath, 'aselna');
                    chgrp($zipPath, 'psacln');
                }


                $zip = new \ZipArchive;
                if ($zip->open($zipPath) === true) {
                    $zip->extractTo($extractPath);
                    $zip->close();

                    //$files = scandir($extractPath);
                    $files = scandir($extractPath, SCANDIR_SORT_DESCENDING);
                    foreach ($files as $key => $file) {
                        if (pathinfo($file, PATHINFO_EXTENSION) === 'xml' && $key == 0) {
                            $filePath = 'result-feeds/ebay/' . $listingId . '/' . $file;
                            $filePath = public_path($filePath);
                            chown($filePath, 'aselna');
                            chgrp($filePath, 'psacln');

                            $xmlContent = file_get_contents($extractPath . '/' . $file);
                            $xml = simplexml_load_string($xmlContent);

                            $feedType = $listingInfo->feed_type;
                            switch ($feedType) {
                                case FeedTypes::CREATE:
                                    $successSku = array();
                                    if (isset($xml->AddFixedPriceItemResponse)) {
                                        $responseObj = $xml->AddFixedPriceItemResponse;
                                    } else if (isset($xml->ReviseFixedPriceItemResponse)) {
                                        $responseObj = $xml->ReviseFixedPriceItemResponse;
                                    }
                                    if (isset($responseObj)) {

                                        $successCnt = 0;
                                        $failCnt = 0;
                                        $warningCnt = 0;
                                        $errorMsg = '';
                                        foreach ($responseObj as $item) {
                                            if (isset($item->Ack) && strtolower($item->Ack) == 'success') {
                                                $successCnt++;
                                                $successSku[] = $item->SKU;
                                                Log::info('SKU : ' . $item->SKU);
                                                //$listingItems = ListingItem::where('sku', $item->SKU)->first();
                                                $listingItems = ListingItem::select('listing_item.*')->leftJoin('listing_item_link', 'listing_item_link.item_id', '=', 'listing_item.id')->where('listing_item.sku', $item->SKU)->where('listing_item_link.listing_id', $listingId)->first();
                                                if (isset($listingItems) && $listingItems->store_sku != $item->ItemID) {
                                                    $listingItems->update([
                                                        'store_sku' => $item->ItemID,
                                                        'sync_status' => ListingSyncStatus::Synchronized
                                                    ]);
                                                }
                                            } else if (isset($item->Ack) && strtolower($item->Ack) == 'warning') {
                                                $warningCnt++;
                                                if (isset($item->Errors)) {
                                                    $errorMsg = $item->Errors->LongMessage;
                                                }
                                            } else {

                                                if (isset($item->Errors)) {
                                                    if (isset($item->Errors->ErrorCode) && $item->Errors->ErrorCode == '21916271') {
                                                        $successCnt++;
                                                    } else {
                                                        $failCnt++;
                                                    }
                                                    $errorMsg = $item->Errors->LongMessage;
                                                }
                                            }
                                        }

                                        if (!empty($successSku)) {
                                            $listing_items = ListingItemLink::select('listing_item_link.item_id')->leftJoin('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')->where('listing_item_link.listing_id', $listingId)->whereNotIn('listing_item.sku', $successSku)->get();
                                            if (count($listing_items ?? []) > 0) {
                                                foreach ($listing_items as $item) {
                                                    $listingItem = ListingItem::findOrFail($item->item_id);
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Unsynchronize
                                                    ]);
                                                }
                                            }
                                        } else {
                                            $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
                                            if (count($listing_items ?? []) > 0) {
                                                foreach ($listing_items as $item) {

                                                    $listingItem = ListingItem::findOrFail($item->item_id);
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Unsynchronize
                                                    ]);
                                                }
                                            }
                                        }

                                        $feedInfo->update([
                                            'success_count' => $successCnt,
                                            'warning_count' => $warningCnt,
                                            'status_check_count' => ($feedInfo->status_check_count != '') ? ($feedInfo->status_check_count + 1) : 0,
                                            'fail_count' => $failCnt,
                                            'error_text' => $errorMsg,
                                            'last_status_check_ts' => date('Y-m-d H:i:s')
                                        ]);

                                        if ($successCnt > 0) {
                                            $feedInfo->update([
                                                'status' => FeedStatus::PROCESSED
                                            ]);
                                        }

                                        $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
                                        if (count($listing_items ?? []) > 0) {
                                            $successCnt = $failCnt = 0;
                                            foreach ($listing_items as $item) {

                                                $listingItem = ListingItem::findOrFail($item->item_id);
                                                if ($listingItem->store_sku != '') {
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Synchronized
                                                    ]);
                                                    $successCnt++;
                                                } else {
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Unsynchronize
                                                    ]);
                                                    $failCnt++;
                                                }
                                            }
                                            $feedInfo->update([
                                                'success_count' => $successCnt,
                                                'fail_count' => $failCnt
                                            ]);
                                        }
                                    }
                                case FeedTypes::DELETE:
                                    break;
                                case FeedTypes::PRICE:
                                case FeedTypes::INVENTORY:
                                    $successSku = array();
                                    if (isset($xml->ReviseInventoryStatusResponse)) {
                                        $successCnt = 0;
                                        $failCnt = 0;
                                        $warningCnt = 0;
                                        $errorMsg = '';
                                        foreach ($xml->ReviseInventoryStatusResponse as $item) {
                                            if (isset($item->Ack) && strtolower($item->Ack) == 'success') {
                                                $successCnt++;
                                                $successSku[] = $item->InventoryStatus->SKU;
                                                Log::info('SKU : ' . $item->InventoryStatus->SKU);
                                                $listingItems = ListingItem::select('listing_item.*')->leftJoin('listing_item_link', 'listing_item_link.item_id', '=', 'listing_item.id')->where('listing_item.sku', $item->InventoryStatus->SKU)->where('listing_item_link.listing_id', $listingId)->first();
                                                if (isset($listingItems)) {
                                                    $listingItems->update([
                                                        'store_sku' => $item->ItemID,
                                                        'sync_status' => ListingSyncStatus::Synchronized
                                                    ]);
                                                }
                                            } else if (isset($item->Ack) && strtolower($item->Ack) == 'warning') {
                                                $warningCnt++;
                                                if (isset($item->Errors)) {
                                                    $errorMsg = $item->Errors->LongMessage;
                                                }
                                            } else {
                                                $failCnt++;
                                                if (isset($item->Errors)) {
                                                    $errorMsg = $item->Errors->LongMessage;
                                                }
                                            }
                                        }

                                        if (!empty($successSku)) {
                                            $listing_items = ListingItemLink::select('listing_item_link.item_id')->leftJoin('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')->where('listing_item_link.listing_id', $listingId)->whereNotIn('listing_item.sku', $successSku)->get();
                                            if (count($listing_items ?? []) > 0) {
                                                foreach ($listing_items as $item) {
                                                    $listingItem = ListingItem::findOrFail($item->item_id);
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Unsynchronize
                                                    ]);
                                                }
                                            }
                                        } else {
                                            $listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
                                            if (count($listing_items ?? []) > 0) {
                                                foreach ($listing_items as $item) {

                                                    $listingItem = ListingItem::findOrFail($item->item_id);
                                                    $listingItem->update([
                                                        'sync_status' => ListingSyncStatus::Unsynchronize
                                                    ]);
                                                }
                                            }
                                        }

                                        $feedInfo->update([
                                            'success_count' => $successCnt,
                                            'warning_count' => $warningCnt,
                                            'status_check_count' => ($feedInfo->status_check_count != '') ? ($feedInfo->status_check_count + 1) : 0,
                                            'fail_count' => $failCnt,
                                            'error_text' => $errorMsg,
                                            'last_status_check_ts' => date('Y-m-d H:i:s')
                                        ]);

                                        if ($successCnt > 0) {
                                            $feedInfo->update([
                                                'status' => FeedStatus::PROCESSED
                                            ]);
                                        }
                                    }
                                    break;

                                default:
                                    break;
                            }

                            /* counting based on store sku*/
                            /*$listing_items = ListingItemLink::select('item_id')->where('listing_id', $listingId)->get();
                            if (count($listing_items ?? []) > 0) {
                                $successCnt = $failCnt = 0;
                                foreach ($listing_items as $item) {

                                    $listingItem = ListingItem::findOrFail($item->item_id);
                                    if($listingItem->store_sku != ''){
                                        $listingItem->update([
                                            'sync_status' => ListingSyncStatus::Synchronized
                                        ]);
                                        $successCnt++;
                                    }else{
                                        $listingItem->update([
                                            'sync_status' => ListingSyncStatus::Unsynchronize
                                        ]);
                                        $failCnt++;
                                    }
                                }
                                $feedInfo->update([
                                    'success_count' => $successCnt,
                                    'fail_count' => $failCnt
                                ]);
                            }*/
                            /* counting based on store sku*/

                            return 'result-feeds/ebay/' . $listingId . '/' . $file;
                        }
                    }
                }
            }
            //$response = $this->authService->getResultFeed('task-1-1275361',$listingId);
            return $response;
        }

        return false;
    }

    /*public function downloadResultFeed($taskID, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $this->authService = new EbayAuthService($store->seller_id, $store->secret_key,$store->access_key,$store->user_id);

        //$feedInfo = ListingFeed::where('listing_id', $listingId)->first();

        $response = $this->authService->getResultFeed('task-1-1274321',$listingId);

        return $response;

    }*/

    public function getInputFeed($taskID, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $authService = new EbayAuthService($store->seller_id, $store->secret_key, $store->access_key, $store->user_id);

        $response = $authService->downloadInputFeed($taskID, $listingId);

        return $response;

    }

    public function getOrders($store)
    {
        $last_synchronization = OrderSynchronization::where('store_id', $store->id)->where('synchronization_type', OrderSyncronisationType::ORDER)->orderByRaw('COALESCE(last_order_update_date,filter_end_date) desc')->first();
        $filterStartDate = OrderUtility::getLastSyncDate($store->id, OrderSyncronisationType::ORDER);
        $filterStartDateFormatted = gmdate('Y-m-d\TH:i:s\Z', strtotime($filterStartDate));

        $this->authService = new EbayAuthService($store->seller_id, $store->secret_key, $store->access_key, $store->user_id);
        $orders = $this->authService->getOrders($filterStartDateFormatted, $last_synchronization->cursor ?? null);
        Log::info('Orders Response : ' . json_encode(array_keys($orders)));

        if (!isset($orders)) {
            Log::info("Error in Walmart Orders : ");
            return null;
        }

        if (isset($orders['total']) && $orders['total'] < 0) {
            Log::info("No Orders Found");
            return null;
        }

        $nextCursor = $orders['next'] ?? null;
        Log::info('Next Cursor : ' . $nextCursor);

        $created_by = auth()->user()->name ?? 'admin';
        $synchronizeArr = [
            'store_id' => $store->id,
            'synchronization_type' => OrderSyncronisationType::ORDER,
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => now(),
            'created_by' => $created_by,
            'cursor' => $nextCursor
        ];
        Log::info('Order Synchronization: ' . json_encode($synchronizeArr));
        $order_synchronization = OrderSynchronization::create($synchronizeArr);

        if (isset($orders['total']) && $orders['total'] > 0) {
            $last_order_update_date = null;
            $noOfOrder = 0;
            foreach ($orders['orders'] as $order) {
                Log::info('Order : ' . json_encode($order));
                $newOrder = Order::where('order_id', $order['orderId'])->first();

                $persist = false;
                $statusChange = false;

                if ($newOrder == null) {
                    $orderData = [
                        'order_id' => $order['orderId'],
                        'order_date' => date('Y-m-d H:i:s', strtotime($order['creationDate'])),
                        'order_status' => OrderStatus::getOrderStatusId($order['orderFulfillmentStatus']),
                        'order_total' => $order['totalFeeBasisAmount']['value'],
                        'buyer_first_name' => $order['buyer']['buyerRegistrationAddress']['fullName'],
                        'buyer_last_name' => '',
                        'buyer_email' => $order['buyer']['buyerRegistrationAddress']['email'] ?? '',
                        'purchase_order_id' => $order['orderId'],
                        'store_id' => $store->id,
                        'order_synchronization_id' => $order_synchronization->id
                    ];
                    $newOrder = Order::create($orderData);
                    $persist = true;
                    $statusChange = true;
                } else {
                    // TODO Need To implement status change
                    $statusChange = true;
                }

                if ($statusChange) {
                    OrderUtility::mapEbayOrders($newOrder, $order, $store);
                }


                if ($last_order_update_date === null || strtotime($order['creationDate']) > strtotime($last_order_update_date)) {
                    $last_order_update_date = date('Y-m-d H:i:s', strtotime($order['creationDate']));
                }
                Log::info('Last Order Update Date : ' . $last_order_update_date);
                $noOfOrder++;
            }
            $order_synchronization->update(['number_of_items' => $noOfOrder, 'last_order_update_date' => $last_order_update_date]);
        }
        return $orders;
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }
}
