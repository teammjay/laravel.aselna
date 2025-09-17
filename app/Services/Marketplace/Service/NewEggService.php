<?php

namespace App\Services\Marketplace\Service;

use App\Constants\ListingStatus;
use App\Constants\OrderStatus;
use App\Models\Order;
use App\Services\Auth\NewEggAuthService;
use App\Utils\FileUtility;
use DateTime;
use App\Constants\FeedStatus;
use App\Constants\FeedTypes;
use App\Constants\ListingSyncStatus;
use App\Constants\OrderSyncronisationType;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItem;
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
use App\Models\SysFile;
use App\Services\Auth\EbayAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\ListingUtility;
use App\Utils\order\OrderUtility;
use App\Utils\Spintax;
use App\Utils\XmlUtility;
use Illuminate\Support\Facades\Log;

class NewEggService implements MarketplaceServiceInterface
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
        $filePath = public_path('feeds/newegg/' . $listingId . '.json');
        /* create listing feed */
        return $filePath;
    }

    public function generateCreateFeed($listingInfo, $listingId)
    {
        // Generate feed for Ebay
        $listingItems = ListingItemLink::with('listingItem', 'listingItem.brand', 'listingItem.devices', 'listingItem.devices.device', 'listingItem.storeProduct', 'listingItem.storeProduct.product', 'listingItem.storeProduct.product.color', 'listingItem.storeProduct.store', 'listingItem.storeProduct.StoreTypeCategory', 'listingItem.storeProduct.productImages')->where('listing_id', $listingId)->get();
        $fileSystem = $listingInfo->store->fileSystem;
        $version = $this->version;

        $feed = [
            "NeweggEnvelope" => [
                "Header" => [
                    "DocumentVersion" => "1.0"
                ],
                "MessageType" => "BatchItemCreation",
                "Message" => [
                    "Itemfeed" => [
                        "SummaryInfo" => [
                            "SubCategoryID" => ''
                        ],
                        "Item" => []
                    ]
                ]
            ]
        ];

        foreach ($listingItems as $key => $listingItem) {
            $item = $listingItem->item;
            $productObj = $item->storeProduct->product;
            $productColor = $productObj->productColor;
            $price = $item->price;
            if (!isset($price)) {
                $price = $item->storeProduct->price;
            }
            if (!isset($price)) {
                $price = $productObj->product_marketplace_price;
            }

            $keyFeatures = [];
            foreach ($productObj->productKeyFeatures as $feature) {
                if ($feature->value == '' || !isset($feature->value)) continue;
                $keyFeatures[] = $feature->value;
            }

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            Log::info('Images: ' . json_encode($images));
            Log::info('File System: ' . json_encode($fileSystem));
            $imagesPath = [];
            $mainImagePath = null;

            foreach ($images as $image) {
                if ($image->productImage && $image->main_image == 0) {
                    $imagesPath[] = [
                        'ImageUrl' => $fileSystem->url_base_path . $image->path
                    ];
                } else {
                    $mainImagePath = $fileSystem->url_base_path . $image->path;
                }
            }

            $itemImages['Image'] = $imagesPath;

            $items = [
                [
                    "Action" => "Create Item",
                    "BasicInfo" => [
                        "SellerPartNumber"       => $item->sku,
                        "Manufacturer"           => $listingInfo->store->name,
                        "ManufacturerPartsNumber" => "MPN001",
                        "UPCOrISBN"              => $item->upc,
                        "WebsiteShortTitle"      => htmlspecialchars($item->title),
                        "BulletDescription"      => implode('^^',$keyFeatures),
                        "ProductDescription"     => htmlspecialchars($item->description),
                        "ItemWeight"             => ($item->storeProduct->product->product_weight * 0.0625) ?? null,
                        "PacksOrSets"             => "1",
                        "Currency"               => "USD",
                        "SellingPrice"           => $price,
                        "Shipping"               => "Free",
                        "Inventory"              => "100",
                        "ActivationMark"         => "True",
                        "ItemCondition"          => "New",
                        "ImageUrl"               => $mainImagePath,
                        "ItemImages"             => $itemImages,
                    ],
                    "SubCategoryProperty" => [
                        "YourSubCategorySpecificFields" => [
                            "Field1" => "Value1",
                            "Field2" => "Value2",
                        ]
                    ],
                    // Optional: custom variety for variants
                    "CustomVariety" => [
                        "CustomVariety1Name"  => "Color",
                        "CustomVariety1Value" => $productColor
                    ]
                ],

            ];

            $feed["NeweggEnvelope"]["Message"]["Itemfeed"]["Item"][] = $items;
        }

        FileUtility::saveToFile(
            public_path('feeds/amazon/' . $listingId . '.json'),
            json_encode($feed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
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

        return $title;
    }

    public function submitFeed($feedId, $listingId)
    {
        $version = $this->version;
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;


        $authService = new NewEggAuthService($store->seller_id, $store->access_key, $store->secret_key);

        $fileName = $listingId . '.json';
        $filePath = public_path('feeds/newegg/' . $fileName);
        //echo $filePath;die;
        Log::info('Feed Id : ' . $feedId);
        $listingDetail = ListingFeed::where('listing_id', $listingId)->first();
        $feed_id = $listingDetail->id;

        $feedInfo = ListingFeed::findOrFail($feed_id);
        $feedInfo->update(['submit_ts' => now(), 'number_of_attempts' => $feedInfo->number_of_attempts ?? 0 + 1]);

        Log::info('File Path : ' . $filePath);
        $response = $authService->submitFeed($filePath);
        Log::info('Feed Submitted : ' . $feedId);

        return response()->json([
            'message' => 'success'
        ], 200);
    }

}
