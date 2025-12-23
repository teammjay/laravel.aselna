<?php

namespace Tests\Jobs;

use App\Jobs\ProcessFeedGenerationJob;
use App\Models\Manufacturer;
use App\Models\ProductType;
use App\Models\ProductTypeStoreTypeCATLink;
use App\Models\StoreType;
use App\Models\StoreTypeManufaturerLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ProcessFeedGenerationJobTest extends TestCase
{

//    private $store_type_detail;

    public function testGetProductCategoryCode()
    {
//        $this->store_type_detail = StoreType::where('code', 'googleShopping')->first();
        // test function from ProcessFeedGenerationJob
//        $categoryCode = $this->getProductCategoryCode('ad350050-6740-4fea-9490-b2de0420a03a');
//        Log::info('Category Code: ' . $categoryCode);

//        $productTitle = 'Samsung Galaxy Note 10 Plus - 10000mAh Power Bank Wireless Charging Backup Battery Portable Charger Slim 2-Port USB  - ONC36';
//        $productTitle = preg_replace('/ +/', ' ', $productTitle);
//        Log::info($productTitle);
//        $manufacturer = Manufacturer::where('id', '679b9f77-218f-4ea6-96d8-51e2278e3321')->first();
//        $manufacturerName = $this->getManufacturerName($manufacturer);
//        Log::info('Manufacturer Name: ' . $manufacturerName);

        $query = DB::connection('easyesale_db')->table('listing_item_product_image')
            ->join('product_image', 'product_image.id', '=', 'listing_item_product_image.product_image_id')
            ->where('product_id', 'd75692a5-d38c-4599-9cc0-25d526a63686')->whereNotNull('reference_id');


        $listingImages = $query->get();
        $image = [];
        foreach ($listingImages as $listingImage) {
            $image = [
                'path' => $listingImage->path,
                'main_image' => $listingImage->main_image,
                'listing_item_id' => $listingImage->listing_item_id,
                'sort_order' => $listingImage->sort_order,
                'code' => $listingImage->code
            ];
        }

        Log::info('Listing Images: ' . json_encode($image));
//            ->where('listing_item_id', $listingItem->id)
//            ->whereNull('product_image_id')
    }

    public function getProductCategoryCode($id)
    {
        $category = ProductType::where('id', $id)->first();
        $store_type_detail = StoreType::where('code', 'googleShopping')->first();

        while (isset($category) && $category->parent_id != null) {
            Log::info('Product Category Code: ' . $category->id);
            $parentCategory = ProductType::where('id', $category->parent_id)->first();

            $categorycode = ProductTypeStoreTypeCATLink::join('store_type_category', 'store_type_category.id', '=', 'product_type_store_type_category_link.channel_category_id')
                ->where('store_type_category.store_type_id', $store_type_detail->id)
                ->where('product_type_id', $category->id)
                ->orderBy('store_type_category.code', 'desc')
                ->select('store_type_category.code');

            Log::info('SQL Query: ' . $categorycode->toRawSql());

            $categorycode = $categorycode->first();

            if (isset($categorycode)) {
                return $categorycode;
            }
            $category = $parentCategory;
        }

        Log::info('Product Category Code: ' . json_encode($categorycode));

        return $categorycode ? $categorycode->code : '';
    }

    private function getManufacturerName($manufacturer)
    {
        if ($manufacturer == null) {
            return '';
        }
        $store_type_detail = StoreType::where('code', 'shopify')->first();
        $manufacturerLink = StoreTypeManufaturerLink::where('store_type_id', $store_type_detail->id)
            ->where('manufacturer_id', $manufacturer->id)
            ->first();
        Log::info('Manufacturer Name 1: ' . json_encode($manufacturerLink));

        if (isset($manufacturerLink)) {
            if (strtolower($manufacturerLink->manufacturer_name) == 'hco') {
                return 'StoreNAme';
            } else {
                return $manufacturerLink->manufacturer_name;
            }
        } else {
            if ($manufacturer->name == 'Use store as Brand' || strtolower($manufacturer->name) == 'hco') {
                return 'StoreName';
            } else {
                return $manufacturer->name;
            }
        }
    }
}
