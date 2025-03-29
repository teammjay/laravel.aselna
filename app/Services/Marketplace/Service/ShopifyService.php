<?php

namespace App\Services\Marketplace\Service;

use App\Jobs\ProductInterfaces;
use App\Models\Listing;
use App\Models\ListingItemLink;
use App\Models\ListingItemProductImage;
use App\Models\ProductInterface;
use App\Models\ProductTags;
use App\Models\ProductTypeKeyword;
use App\Services\Auth\ShopifyAuthService;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use Illuminate\Support\Facades\Log;

class ShopifyService implements MarketplaceServiceInterface
{
    public function generateFeed($listingId)
    {

    }

    public function submitFeed($feedId, $listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
        $fileSystem = $listingInfo->store->fileSystem;
        $store = $listingInfo->store;

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        foreach ($listingItems as $item) {
            $colorOption = [];
            $productOptions = [];
            $listingItem = $item->listingItem;
            $storeProduct = $listingItem->storeProduct;
            $productData = $listingItem->storeProduct->product;
            Log::info('Listing Item: ' . json_encode($listingItem));
//
//            $collectionIds = $this->getCollectionIds($listingItem->devices);
//            continue;

            $descriptionHtml = '';
            $descriptionHtml .= '<ul>';
            foreach ($storeProduct->features as $feature) {
                $safeText = htmlspecialchars($feature->value, ENT_QUOTES | ENT_XML1, 'UTF-8'); // Escapes special characters
                if (!empty(trim($safeText))) {
                    $descriptionHtml .= '<li>' . $safeText . '</li>';
                }
            }

            $descriptionHtml .= '</ul>';
            $descriptionHtml .= '<h2>Product Description</h2>';
            $descriptionHtml .= '<p>' . $listingItem->description . '</p>';

            $tags = [];
            $tags = $this->getAllTags($productData, $tags);
            Log::info('Tags: ' . json_encode($tags));
            $tagsString = implode(',', $tags);

            $productOptions[] = [
                'name' => 'Color',
                'values' => [
                    ['name' => $productData->productColor->name,]
                ]
            ];

            $images = ListingItemProductImage::where('listing_item_id', $item->id)->get();
            $files = [];
            foreach ($images as $image) {
                $files[] = [
                    'contentType' => 'IMAGE',
                    'originalSource' => $fileSystem->url_base_path . $image->path,
                ];
            }

            if (env('APP_ENV') == 'local') {
                $files = [
                    [
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_cdf4e35c-b0f0-48b6-b3a3-9bfd6ddfca9a.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_f637dfb1-0716-433a-9708-a55b8c109a56.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_e06629ca-3b9e-4855-a169-ab2d2daf604d.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_e032abc4-e121-4048-8004-3fafd6e4d0f7.jpg',
                    ],[
                        'contentType' => 'IMAGE',
                        'originalSource' => 'http://uzve.com/ftp_images/store-images/f5ebde6c-2bcc-03f5-4d62-ef4692f87314/70cfd4a9-0163-4d8e-b248-5b5b19298583_6974c6a4-1631-420c-9788-83b3c9bbde05.jpg',
                    ],
                ];
            }

            $productNewData = [
                'productSet' => array_filter([
                    'title' => $listingItem->title,
                    'descriptionHtml' => $descriptionHtml,
                    'vendor' => $store->name,
                    'productType' => $productData->productType->name,
                    'tags' => $tagsString,
                    'productOptions' => $productOptions,
                    'files' => $files,
                    'variants' => [
                        [
                            'price' => $listingItem->price,
                            'sku' => $listingItem->sku,
                            'barcode' => $listingItem->upc,
                            'optionValues' => [
                                [
                                    'optionName' => 'Color',
                                    'name' => $productData->productColor->name,
                                ]
                            ],
                        ]
                    ],
                ], function ($value) {
                    return !is_null($value);
                }),
            ];

            Log::info('Product Data: ' . json_encode($productNewData));

            $shopifyAuthService = new ShopifyAuthService($store->secret_key);
            $response = $shopifyAuthService->saveProduct($productNewData);
//             get handle and id from response and save in listing_item table
            Log::info('Product: ' . json_encode($response));

//            $listingItem->update([
//                'store_url' => $response['handle'],
//            ]);
        }
    }

    public function getResultFeed(mixed $listingId,$feedId = null)
    {

    }

    public function syncAttributes($store)
    {

    }

    public function getAllTags($productData, array $tags): array
    {
        ProductTags::where('product_id', $productData['product_id'])->get()->each(function ($tag) use (&$tags) {
            $tags[] = $tag->name;
        });

        ProductInterface::where('product_id', $productData['product_id'])->get()->each(function ($interface) use (&$tags) {
            $tags[] = $interface->interface_id;
        });

        if (isset($productData->productColor) && !empty($productData->productColor->name)) {
            $tags[] = 'color_' . $productData->productColor->name;
            $tags[] = $productData->productColor->name;
        }

        if (isset($productData->material) && !empty($productData->material->name)) {
            $tags[] = 'material_' . $productData->material->name;
        }

        if (isset($productData->size) && !empty($productData->size->value)) {
            $tags[] = 'size_' . $productData->size->value;
        }

        if (isset($productData->productType) && !empty($productData->productType->id)) {
            $tags[] = $productData->productType->id;
        }

        if (isset($productData->productType) && !empty($productData->productType->name)) {
            $tags[] = $productData->productType->name;
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->id)) {
            $tags[] = $productData->productType->parent->id;
        }

        if (isset($productData->productType) && !empty($productData->productType->parent) && !empty($productData->productType->parent->name)) {
            $tags[] = $productData->productType->parent->name;
        }

        $tags[] = 'upsell-enable';
        return $tags;
    }

    public function getOrders()
    {
        $this->authService = new ShopifyAuthService('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');
        $response = $this->authService->getOrders();

        return $response;
    }

    private function getCollectionIds($devices)
    {
        foreach ($devices as $device) {
            $deviceData = $device->device;
            Log::info('Device: ' . json_encode($device->device));
        }
    }
}
