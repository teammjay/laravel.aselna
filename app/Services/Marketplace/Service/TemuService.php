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

            foreach (json_decode($allfeedContent, true) as $individualFeed) {
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

            $productData = [
                'Brand' => $listingItem->brand->name ?? 'APPLE',
                'Color' => 'Black',
            ];

            $TemplateProperties = $template['result']['templateInfo']['goodsProperties'] ?? [];
            $properties = $this->buildGoodsPropertiesPayload($TemplateProperties, $productData);
            Log::info('Properties completed');

            $TemplateSpecProperties = $template['result']['templateInfo']['goodsSpecProperties'] ?? [];
            $specDetails = $this->getSpecDetails($TemplateSpecProperties,$productData);
            $properties = array_merge($properties,$specDetails['properties']);

            $specIdList = $specDetails['specIdList'];
            Log::info('Properties :: ' . json_encode($properties));



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
                    'importDesignation' => 'Made in the USA',
                ],
//                'goodsDesc' => $item->description,
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
                    'length' => '' . $productObj->package_dimensions_length,
                    'price' => [
                        'basePrice' => [
                            'amount' => '' . floatval($price),
                            'currency' => 'USD'
                        ],
//                        'listPrice' => [
//                            'amount' => floatval($price),
//                            'currency' => 'USD'
//                        ],
                        'listPriceType' => 0
                    ],
                    'quantity' => $item->quantity,
                    'specIdList' => $specIdList,
                    'outSkuSn' => $item->sku,
                    'volumeUnit' => 'in',
                    'weight' => '' . WeightUnit::convert($productObj->product_weight_unit ?? WeightUnit::OZ, WeightUnit::LB, $productObj->product_weight),
                    'weightUnit' => 'lb',
                    'width' => '' . $productObj->package_dimensions_width,
                ]
            ];


            $jsonFeed[] = $feedItem;
        }

        Log::info('Json Feed :: '. json_encode($jsonFeed));

        FileUtility::saveToFile(public_path('feeds/temu/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    public function buildGoodsPropertiesPayload(array $templateAttributes, array $productData): array
    {
        $finalPayload = [];

        // Create a simple map of your product data for easy lookup.
        // This assumes keys in $productData match the 'name' from the template.
        $productValueMap = $productData;

        foreach ($templateAttributes as $attribute) {
            $attributeName = $attribute['name'];
            Log::info('Attribute Name :: ' . $attributeName);

            // Skip if your product data doesn't have a value for this attribute.
            if (!isset($productValueMap[$attributeName])) {
                continue;
            }

            $valueFromDb = $productValueMap[$attributeName];
            Log::info('Attribute :: ' . json_encode($attribute));

            switch ($attribute['controlType']) {
                /**
                 * Case 1: Select type property (Dropdowns, Checkboxes)
                 * We need to find the matching 'vid' for the given value string.
                 */
                case 1:
                case 3:
                    // Normalize DB value to an array to handle multi-select gracefully.
                    $selectedValues = is_array($valueFromDb) ? $valueFromDb : [$valueFromDb];

                    // Create a lookup map for faster searching: ['Cotton' => ['vid' => 49, ...]]
                    $optionsMap = array_column($attribute['values'], null, 'value');

                    foreach ($selectedValues as $selectedValue) {
                        if (isset($optionsMap[$selectedValue])) {
                            $chosenOption = $optionsMap[$selectedValue];
                            $finalPayload[] = [
                                'templatePid' => $attribute['templatePid'],
                                'refPid'      => $attribute['refPid'],
                                'pid'         => $attribute['pid'],
                                'vid'         => $chosenOption['vid'],
                                'value'       => $chosenOption['value'],
                            ];
                        }
                    }
                    break;

                case 0:
                    $payloadObject = [
                        'templatePid' => $attribute['templatePid'],
                        'refPid'      => $attribute['refPid'],
                        'pid'         => $attribute['pid'],
                        'vid'         => 0, // 'vid' is 0 for input types as per docs.
                        'value'       => (string) $valueFromDb,
                    ];

                    // Check if a unit is defined and add it to the payload.
                    if (!empty($attribute['valueUnitList'])) {
                        // Using the first available unit by default.
                        // You might need more logic if multiple units are possible.
                        $payloadObject['valueUnit'] = $attribute['valueUnitList'][0]['valueUnit'];
                    }

                    $finalPayload[] = $payloadObject;
                    break;
            }
        }

        return $finalPayload;
    }

    private function getSpecDetails(array $templateAttributes, array $productData): array
    {
        $finalPayload = [
            'properties' => [],
            'specIdList' => []
        ];

        // Create a simple map of your product data for easy lookup.
        // This assumes keys in $productData match the 'name' from the template.
        $productValueMap = $productData;

        foreach ($templateAttributes as $attribute) {
            $attributeName = $attribute['name'];
            Log::info('Attribute Name :: ' . $attributeName);

            // Skip if your product data doesn't have a value for this attribute.
            if (!isset($productValueMap[$attributeName])) {
                continue;
            }

            $valueFromDb = $productValueMap[$attributeName];
            Log::info('Attribute :: ' . json_encode($attribute));

            switch ($attribute['controlType']) {
                /**
                 * Case 1: Select type property (Dropdowns, Checkboxes)
                 * We need to find the matching 'vid' for the given value string.
                 */
                case 1:
                case 3:
                    // Normalize DB value to an array to handle multi-select gracefully.
                    $selectedValues = is_array($valueFromDb) ? $valueFromDb : [$valueFromDb];

                    // Create a lookup map for faster searching: ['Cotton' => ['vid' => 49, ...]]
                    $optionsMap = array_column($attribute['values'], null, 'value');

                    foreach ($selectedValues as $selectedValue) {
                        if (isset($optionsMap[$selectedValue])) {
                            $chosenOption = $optionsMap[$selectedValue];
                            $finalPayload['properties'][] = [
                                'templatePid' => $attribute['templatePid'],
                                'refPid'      => $attribute['refPid'],
                                'pid'         => $attribute['pid'],
                                'vid'         => $chosenOption['vid'],
                                'specId'       => $chosenOption['specId'],
                            ];
                            $finalPayload['specIdList'][] = $chosenOption['specId'];
                        }
                    }
                    break;
            }
        }

        return $finalPayload;
    }
}
