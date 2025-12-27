<?php

namespace App\Utils;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\ProductType;
use App\Models\Store;
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
        $skipDevicePages = false;
        if ($store_id) {
            $store = Store::where('id', $store_id)->first();
            if ($store && $store->store_type_id == 1) {
                $skipDevicePages = true;
            }
        }
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
                $popularLinks = StoreNavigationPage::join('brands', 'store_navigation_page.device_brand_id', '=', 'brands.id')
                    ->where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICES)
                    ->where('brands.order_no', '<=', 10)
                    ->orderBy('brands.order_no', 'asc')
                    ->get();

                $otherLinks = StoreNavigationPage::join('brands', 'store_navigation_page.device_brand_id', '=', 'brands.id')
                    ->where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICES)
                    ->where('brands.order_no', '>', 10)
                    ->orderBy('brands.name', 'asc')
                    ->get();

                $finalLinkArray['final_title'] = 'Shop By Brand';
                foreach ($popularLinks as $link) {
                    $brandPage = StoreNavigationPage::where('store_id', $store_id)
                        ->where('page_type', WebStorePageType::BRAND)
                        ->where('device_brand_id', $link->device_brand_id)
                        ->first();
                    $finalLinkArray['popular_link'][] = [
                        'id' => $link->id,
                        'title' => $link->brand->name,
                        'imageUrl' => $brandPage->store_image_url,
                        'url' => "/pages/" . $link->url_path,
                        'popular' => true
                    ];
                }

                foreach ($otherLinks as $link) {
                    $brandPage = StoreNavigationPage::where('store_id', $store_id)
                        ->where('page_type', WebStorePageType::BRAND)
                        ->where('device_brand_id', $link->device_brand_id)
                        ->first();
                    $finalLinkArray['other_link'][] = [
                        'id' => $link->id,
                        'title' => $link->brand->name,
                        'imageUrl' => $brandPage->store_image_url,
                        'url' => "/pages/" . $link->url_path,
                        'popular' => false
                    ];
                }
                break;

            case WebStorePageType::BRAND_DEVICES:
                $brand = Brand::where('id', $request->device_brand_id)->firstOrFail();
                $popularLinks = StoreNavigationPage::join('device', 'store_navigation_page.device_id', '=', 'device.id')
                    ->join('popular_devices','store_navigation_page.device_id', '=', 'popular_devices.device_id')
                    ->where('store_id', $store_id)
                    ->where('page_type', $skipDevicePages ? WebStorePageType::DEVICE : WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES)
                    ->where('device_brand_id', $brand->id)
                    ->orderBy('device.device_type_id', 'asc')
                    ->orderBy('device.release_date', 'desc')
                    ->get();

                // I want join here to get all devices which are not in popular devices
                $otherLinks = StoreNavigationPage::join('device', 'store_navigation_page.device_id', '=', 'device.id')
                    ->whereNotIn('store_navigation_page.device_id', function ($query) use ($store_id, $brand) {
                        $query->select('device_id')
                            ->from('popular_devices');
                    })
                    ->where('store_id', $store_id)
                    ->where('page_type', $skipDevicePages ? WebStorePageType::DEVICE : WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES)
                    ->where('device_brand_id', $brand->id)
                    ->orderBy('device.name', 'asc')
                    ->get();

                $finalLinkArray['final_title'] = $brand->name;
                foreach ($popularLinks as $link) {
                    $devicePage = StoreNavigationPage::where('store_id', $store_id)
                        ->where('page_type', WebStorePageType::DEVICE)
                        ->where('device_id', $link->device_id)
                        ->first();

                    $finalLinkArray['popular_link'][] = [
                        'id' => $link->id,
                        'title' => $link->device->name,
                        'imageUrl' => $devicePage->store_image_url,
                        'url' => ($skipDevicePages ? "/collections/" : "/pages/") . $link->url_path,
                        'popular' => true
                    ];
                }

                foreach ($otherLinks as $link) {
                    $devicePage = StoreNavigationPage::where('store_id', $store_id)
                        ->where('page_type', WebStorePageType::DEVICE)
                        ->where('device_id', $link->device_id)
                        ->first();

                    $finalLinkArray['other_link'][] = [
                        'id' => $link->id,
                        'title' => $link->device->name,
                        'imageUrl' => $devicePage->store_image_url,
                        'url' => ($skipDevicePages ? "/collections/" : "/pages/") . $link->url_path,
                        'popular' => false
                    ];
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

                $productTypeMapQuery = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
                    ->join('products', 'products.product_id', '=', 'product_interface.product_id')
                    ->join('product_type', 'product_type.id', '=', 'products.product_type')
                    ->join('device', 'device_interface.device_id', '=', 'device.id')
                    ->where('device.status', 'active')
                    ->where('products.product_status', 'active')
                    ->where('device_interface.device_id', $request->device_id)
                    ->groupBy('product_type.parent_id')
                    ->select('product_type.parent_id','count(products.product_id) as product_count')
                    ->get();

                $productTypeMap = [];
                foreach ($productTypeMapQuery as $ptm) {
                    $productTypeMap[$ptm->parent_id] = $ptm->product_count;
                }

                $device = Device::where('id', $request->device_id)->firstOrFail();

                $finalLinkArray['final_title'] = $device->brand->name . ' ' . $device->name . ' Accessories';
                foreach ($links as $link) {
                    // skip product types which do not have products for this device
                    if (!array_key_exists($link->product_type_id, $productTypeMap)) {
                        continue;
                    }
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
