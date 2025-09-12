<?php

namespace App\Utils;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\ProductType;
use App\Models\StoreNavigationPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;

class Navigationutility
{
    public static function fillTemplateWithData(string $templateString, $templateData)
    {
        try {
            return Blade::render($templateString, $templateData);
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 0, $e);
        }
    }

    public static function getLinks(mixed $store_id, mixed $page_type, Request $request): array
    {
        $finalLinkArray = [];
        switch ($page_type) {
            case WebStorePageType::ALL_PRODUCT_TYPES:
                $finalLinkArray['final_title'] = 'Product Category';
                break;
            case WebStorePageType::PRODUCT_TYPE_BRANDS:
                $productType = ProductType::where('id', $request->product_type)->firstOrFail();
                $finalLinkArray['final_title'] = $productType->name;
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->get();
                foreach ($links as $link) {
                    if ($link->brand->sort_order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

//            case WebStorePageType::PRODUCT_TYPE_BRAND_DEVICES:
//                $device = Device::where('id', $request->device_id)->firstOrFail();
//                $finalLinkArray['final_title'] = $device->name . ' for ' . $device->brand->name;
//                $links = StoreNavigationPage::where('store_id', $store_id)
//                    ->where('page_type', WebStorePageType::DEVICE)
//                    ->get();
//                foreach ($links as $link) {
//                    if ($link->device->order <= 10) {
//                        $finalLinkArray['popular_link'][] = [
//                            'id' => $link->id,
//                            'title' => $link->device->name,
//                            'imageUrl' => $link->store_image_url,
//                            'url' => "/pages/" . $link->url_path,
//                            'popular' => true
//                        ];
//                    } else {
//                        $finalLinkArray['other_link'][] = [
//                            'id' => $link->id,
//                            'title' => $link->device->name,
//                            'imageUrl' => $link->store_image_url,
//                            'url' => "/pages/" . $link->url_path,
//                            'popular' => false
//                        ];
//                    }
//                }
//                break;

            case WebStorePageType::ALL_BRANDS:
                // join device table with device_brand_id
                $links = StoreNavigationPage::join('brands', 'store_navigation_page.device_brand_id', '=', 'brands.id')
                    ->where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICES)
                    ->orderBy('brands.order_no', 'asc')
                    ->get();
                $finalLinkArray['final_title'] = 'Shop By Brand';
                foreach ($links as $link) {
                    $brandPage = StoreNavigationPage::where('store_id', $store_id)
                        ->where('page_type', WebStorePageType::BRAND)
                        ->where('device_brand_id', $link->device_brand_id)
                        ->first();
                    if ($link->brand->sort_order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $brandPage->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->brand->name,
                            'imageUrl' => $brandPage->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::BRAND_DEVICES:
                $brand = Brand::where('id', $request->device_brand_id)->firstOrFail();
                $links = StoreNavigationPage::join('device', 'store_navigation_page.device_id', '=', 'device.id')
                    ->where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_brand_id', $brand->id)
                    ->orderBy('device.order', 'asc')
                    ->get();
                $finalLinkArray['final_title'] = $brand->name;
                foreach ($links as $link) {
                    if ($link->device->order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/collections/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/collections/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::PRODUCT_TYPE:
                break;

            case WebStorePageType::DEVICE_PRODUCT_TYPE:
                break;

            case WebStorePageType::ALL_DEVICES:
                break;

            case WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES:
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                    ->get();
                $deviceCollectionPage = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_id', $request->device_id)
                    ->first();

                $device = Device::where('id', $request->device_id)->firstOrFail();

                $finalLinkArray['final_title'] = $device->brand->name . ' ' . $device->name . ' Accessories';
                foreach ($links as $link) {
                    $finalLinkArray['popular_link'][] = [
                        'id' => $link->id,
                        'title' => $link->productType->name,
                        'imageUrl' => $link->store_image_url,
                        'url' => "/collections/" . $deviceCollectionPage->url_path . "/" . $link->url_path,
                        'popular' => true
                    ];
                }
                break;

            default:
                break;
        }

        return $finalLinkArray;
    }
}
