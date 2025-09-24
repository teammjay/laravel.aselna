<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Constants\WeightUnit;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Services\Auth\TemuAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;
use Illuminate\Support\Facades\Log;

class TemuService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId, $feedId = null)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $feedInfo = ListingFeed::where('id', $feedId)->first();

        switch ($feedInfo->feed_type) {
            case FeedTypes::CREATE:
                $this->generateCreateFeed($listingInfo, $listingId);
                break;
//            case FeedTypes::IMAGE_UPDATE:
//                $this->generateImageUpdateFeed($listingInfo, $listingId);
//                break;
//            case FeedTypes::DELETE:
//                $this->generateDeleteFeed($listingInfo, $listingId);
//                break;
//            case FeedTypes::INVENTORY:
//                $this->generateInventoryFeed($listingInfo, $listingId, $feedInfo);
//                break;
//            case FeedTypes::SHIPMENT_UPDATE:
//                $this->callShipmentUpdateFeed($feedId);
//                break;
//            case FeedTypes::ORDER_CANCEL:
//                $this->callOrderCancelFeed($feedId);
//                break;
//            case FeedTypes::PRICE:
//                $this->generatePriceFeed($listingInfo, $listingId);
//                break;
            default:
                break;
        }

        return 'feeds/temu/' . $listingId . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        $feedInfo = ListingFeed::findOrFail($feedId);
        $listingInfo = Listing::where('id', $listingId)->first();
        $store = $listingInfo->store;

        $temuAuthService = new TemuAuthService();
        $file = public_path('feeds/temu/' . $listingId . '.json');
        // json content
        $allfeedContent = file_get_contents($file);

        foreach (json_decode($allfeedContent,true) as $individualFeed) {
            Log::info('Individual Feed: ' . json_encode($individualFeed));
            $response = $temuAuthService->submitFeed($individualFeed, $store);

            Log::info('Temu Feed Submission Response: ' . json_encode($response));
        }
    }

    public function getResultFeed(mixed $listingId, $feedId = null)
    {
        // TODO: Implement getResultFeed() method.
    }

    public function syncAttributes($store)
    {
        // TODO: Implement syncAttributes() method.
    }

    private function generateCreateFeed($listingInfo, $listingId)
    {
        $temuAuthService = new TemuAuthService();
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        $fileSystem = $listingInfo->store->fileSystem;

        $jsonFeed = [];

        foreach ($listingItems as $listingItem) {
            $item = $listingItem->item;
            $storeTypeCategory = $item->storeProduct->storeTypeCategory;
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

            $template = $temuAuthService->getTemplate($storeTypeCategory->code);
            Log::info('Template for Category ' . $storeTypeCategory->code . ': ' . json_encode($template));

            $properties = [];
            $TemplateProperties = $template['result']['templateInfo']['goodsProperties'] ?? [];
            foreach ($TemplateProperties as $property) {
                if($property['isSale'] == false) {
                    switch ($property['controlType']) {
                        case 0 :
                            $properties[] = [
                                'templatePid' => $property['templatePid'],
                                'refPid' => $property['refPid'],
                                'pid' => $property['pid'],
                                'vid' => 0,
                                'value' => ''
                            ];
                            break;
                        case 1 :
                            $properties[] = [
                                'templatePid' => $property['templatePid'],
                                'refPid' => $property['refPid'],
                                'pid' => $property['pid'],
                                'vid' => $property['values'][0]['vid'],
                                'value' => $property['values'][0]['vid']
                            ];
                            break;
                    }
                }
            }

            $ShippingTemplates = $temuAuthService->geShippingTemplate();
            Log::info('Shipping Templates: ' . json_encode($ShippingTemplates));

            $templateID = $ShippingTemplates['result']['templateList'][0]['templateId'] ?? 'LFT-15741247532810352765';

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            Log::info('Images: ' . json_encode($images));
            Log::info('File System: ' . json_encode($fileSystem));
            $imagesPath = [];
            foreach ($images as $image) {
                $imagesPath[] = $fileSystem->url_base_path . $image->path;
            }

            $feedItem = [
                'goodsBasic' => [
                    'goodsName' => $item->title,
                    'catId' => (int)$storeTypeCategory->store_type_code,
                    'importDesignation' => 'Made in USA',
                ],
                'goodsDesc' => $item->description,
                'goodsServicePromise' => [
                    'costTemplateId' => $templateID,
                    'fulfillmentType' => 1,
                    'shipmentLimitDay' => 2
                ],
                'goodsProperty' => [
                    'goodsProperties' => $properties
                ]
            ];

            $feedItem['skuList'] = [
                [
                    'height' => '' . $productObj->package_dimensions_height,
                    'images' => $imagesPath,
                    'length' => ''. $productObj->package_dimensions_length,
                    'price' => [
                        'basePrice' => [
                            'amount' => floatval($price),
                            'currency' => 'USD'
                        ],
//                        'listPrice' => [
//                            'amount' => floatval($price),
//                            'currency' => 'USD'
//                        ],
                        'listPriceType' => 0
                    ],
                    'quantity' => $item->quantity,
                    'specIdList' => [],
                    'outSkuSn' => $item->sku,
                    'volumeUnit' => 'inches',
                    'weight' => ''.floatval($productObj->product_weight),
                    'weightUnit' => WeightUnit::getUnitName($productObj->product_weight_unit),
                    'width' => ''.$productObj->package_dimensions_width,
                ]
            ];


            $jsonFeed[] = $feedItem;
        }

        FileUtility::saveToFile(public_path('feeds/temu/' . $listingId . '.json'), json_encode($jsonFeed));
    }
}
