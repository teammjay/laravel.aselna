<?php

namespace App\Services\Marketplace\Service;

use App\Jobs\ProductInterfaces;
use App\Models\Listing;
use App\Models\ListingItemLink;
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
        $store = $listingInfo->store;

        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();
        foreach ($listingItems as $item) {
            $listingItem = $item->listingItem;
            $storeProduct = $listingItem->storeProduct;
            $productData = $listingItem->storeProduct->product;
            Log::info('Listing Item: ' . json_encode($listingItem));


            $tags = [];
            $tags = $this->getAllTags($productData, $tags);
            Log::info('Tags: ' . json_encode($tags));
            $listingItem->tags = implode(',', $tags);

            $productData = [
                'input' => array_filter([
                    'title' => $listingItem->title,
                    'descriptionHtml' => $listingItem->description,
                    'vendor' => $listingItem->brand->name,
                    'product_type' => $listingItem->category,
                    'tags' => $listingItem->tags,
                ], function ($value) {
                    return !is_null($value);
                }),
            ];

            Log::info('Product Data: ' . json_encode($productData));

            $shopifyAuthService = new ShopifyAuthService($store->secret_key);
            $response = $shopifyAuthService->saveProduct($productData);
            // get handle and id from response and save in listing_item table
            Log::info('Product: ' . json_encode($response));

            $handle = $response['data']['productCreate']['product']['handle'];
            Log::info('Handle: ' . $handle);

            $listingItem->update([
                'store_url' => $handle,
            ]);
        }
    }

    public function getResultFeed(mixed $listingId)
    {

    }

    public function syncAttributes($store)
    {

    }

    /**
     * @param $productData
     * @param array $tags
     * @return array
     */
    public function getAllTags($productData, array $tags): array
    {
        ProductTags::where('product_id', $productData['product_id'])->get()->each(function ($tag) use (&$tags) {
            $tags[] = $tag->name;
        });

        ProductInterface::where('product_id', $productData['product_id'])->get()->each(function ($interface) use (&$tags) {
            $tags[] = $interface->interface_id;
        });

        if (isset($productData->productColor) && !empty($productData->productColor->name)) {
            $tags[] = 'color-' . $productData->productColor->name;
        }

        if (isset($productData->material) && !empty($productData->material->name)) {
            $tags[] = 'material-' . $productData->material->name;
        }

        if (isset($productData->size) && !empty($productData->size->value)) {
            $tags[] = 'size-' . $productData->size->value;
        }

        $tags[] = 'upsell-enable';
        return $tags;
    }
}
