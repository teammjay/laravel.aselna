<?php

namespace App\Services\Marketplace\Service;

use App\Constants\FeedTypes;
use App\Models\Listing;
use App\Models\ListingItemLink;
use App\Services\Marketplace\Contracts\MarketplaceServiceInterface;
use App\Utils\FileUtility;

class MagentoService implements MarketplaceServiceInterface
{

    public function generateFeed($listingId)
    {
        $listingInfo = Listing::where('id', $listingId)->first();
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
        return 'feeds/magento/' . $listingId . '.json';
    }

    public function submitFeed($feedId, $listingId)
    {
        // TODO: Implement submitFeed() method.
    }

    public function getResultFeed(mixed $listingId)
    {
    }

    private function generateCreateFeed($listingInfo, $listingId)
    {
        // create a feed to insert the products in magento
        $listingItems = ListingItemLink::where('listing_id', $listingId)->cursor();

        $jsonFeed = [];
        foreach ($listingItems as $listingItem) {
            $itemDetail = $listingItem->item;
            $product = $itemDetail->storeProduct->product;
            $searchTerms = $product->searchTerms;
            //combine the search terms with spaces
            $searchTermCombined = implode(' ', $searchTerms->pluck('term')->toArray());
            // populate jsonFeed like product_mapping_simple
            $safeTextTitle = htmlspecialchars($itemDetail->title, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $jsonFeed[] = [
                'name' => $safeTextTitle,
                'sku' => $itemDetail->sku,
                'price' => $itemDetail->price,
                'weight' => $listingItem->weight,
                'custom_attributes' => [
                    [
                        'attribute_code' => 'meta_title',
                        'value' => $listingItem->title
                    ],
                    [
                        'attribute_code' => 'meta_keyword',
                        'value' => $searchTermCombined,
                    ],
                    [
                        'attribute_code' => 'meta_description',
                        'value' => $listingItem->description
                    ],
//                    [
//                        'attribute_code' => 'devices',
//                        'value' => $listingItem->magento_device_id
//                    ],
//                    [
//                        'attribute_code' => 'brands',
//                        'value' => $listingItem->mage_brand_id
//                    ],
                    [
                        'attribute_code' => 'short_description',
                        'value' => $listingItem->description
                    ],
//                    [
//                        'attribute_code' => 'category_ids',
//                        'value' => $listingItem->category_ids
//                    ],
//                    [
//                        'attribute_code' => 'bullet_points',
//                        'value' => $listingItem->bullet_points
//                    ],
                    [
                        'attribute_code' => 'width',
                        'value' => $product->package_dimensions_width
                    ],
                    [
                        'attribute_code' => 'height',
                        'value' => $listingItem->package_dimensions_height
                    ],
                    [
                        'attribute_code' => 'depth',
                        'value' => $listingItem->package_dimensions_length
                    ],
//                    [
//                        'attribute_code' => 'screen_length',
//                        'value' => $listingItem->screen_length
//                    ],
//                    [
//                        'attribute_code' => 'case_size',
//                        'value' => $listingItem->case_size
//                    ]
                ],
                'extension_attributes' => [
                    'stock_item' => [
                        'qty' => 100,
                        'is_in_stock' => true
                    ]
                ]
            ];
        }

        FileUtility::saveToFile(public_path('feeds/magento/' . $listingId . '.json'), json_encode($jsonFeed));
    }

    private function generateImageUpdateFeed($listingInfo, $listingId)
    {
    }

    private function generateDeleteFeed($listingInfo, $listingId)
    {
    }

    private function generateInventoryFeed($listingInfo, $listingId)
    {
    }

    private function generatePriceFeed($listingInfo, $listingId)
    {
    }
}
