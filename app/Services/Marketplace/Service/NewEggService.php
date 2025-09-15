<?php

namespace App\Services\Marketplace\Service;

use App\Constants\ListingStatus;
use App\Constants\OrderStatus;
use App\Models\Order;
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

            $items = [
                [
                    "Action" => "Create Item",
                    "BasicInfo" => [
                        "SellerPartNumber"       => "SKU001",
                        "Manufacturer"           => "YourBrand",
                        "ManufacturerPartsNumber" => "MPN001",
                        "UPCOrISBN"              => "123456789012",
                        "WebsiteShortTitle"      => "My Item Title",
                        "BulletDescription"      => "Feature one^^Feature two^^Feature three",
                        "ProductDescription"     => "This is a detailed description.",
                        "ItemWeight"             => "2.5",
                        "PacksOrSets"             => "1",
                        // Optionally: ItemDimension etc
                        "Currency"               => "USD",
                        "SellingPrice"           => "49.99",
                        "Shipping"               => "Free",
                        "Inventory"              => "100",
                        "ActivationMark"         => "True",
                        "ItemCondition"          => "New",
                        // etc
                    ],
                    "SubCategoryProperty" => [
                        "YourSubCategorySpecificFields" => [
                            "Field1" => "Value1",
                            "Field2" => "Value2",
                            // etc
                        ]
                    ],
                    // Optional: custom variety for variants
                    "CustomVariety" => [
                        "CustomVariety1Name"  => "Color",
                        "CustomVariety1Value" => "Red",
                        "CustomVariety2Name"  => "Size",
                        "CustomVariety2Value" => "M",
                        // etc
                    ]
                ],
                // more items...
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

}
