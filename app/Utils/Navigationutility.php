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
            if ($store && $store->skip_device_pages == 1) {
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
                    if (!isset($link->brand)) continue;
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
                    ->where('brands.status', 'active')
                    ->orderBy('brands.order_no', 'asc')
                    ->get();

                $otherLinks = StoreNavigationPage::join('brands', 'store_navigation_page.device_brand_id', '=', 'brands.id')
                    ->where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICES)
                    ->where('brands.order_no', '>', 10)
                    ->where('brands.status', 'active')
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
                    $count = StoreNavigationPage::where('store_id', $store_id)
                        ->where('device_brand_id', $link->device_brand_id)
                        ->where('page_type', $skipDevicePages ? WebStorePageType::DEVICE : WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES)
                        ->count();

                    if ($count <= 0) {
                        continue; // skip brands with no devices
                    }

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
                    ->where('device.status', 'active')
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
                    ->where('device.status', 'active')
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
                $productTypes = ProductType::whereIn('parent_id', function ($subquery) {
                    $subquery->select('id')->from('product_type')->where('code', 'cea');
                })->whereNotNull('sort_order')->orderBy('sort_order', 'asc')->get();

                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::PRODUCT_TYPE)
                    ->whereIn('product_type_id', $productTypes->pluck('id'))
                    ->get();

                $deviceCollectionPage = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_id', $request->device_id)
                    ->first();

                $productTypeMapQuery = DeviceInterface::join('product_interface', 'device_interface.interface_id', '=', 'product_interface.interface_id')
                    ->join('products', 'products.product_id', '=', 'product_interface.product_id')
                    ->join('product_type as child_pt', 'child_pt.id', '=', 'products.product_type')
                    ->leftJoin('product_type as parent_pt', function($join) {
                        $join->on('parent_pt.id', '=', 'child_pt.parent_id')
                             ->where('parent_pt.code', '!=', 'cea');
                    })
                    ->join('device', 'device_interface.device_id', '=', 'device.id')
                    ->where('device.status', 'active')
                    ->where('products.product_status', 'active')
                    ->where('device_interface.device_id', $request->device_id)
                    ->groupBy(\DB::raw('COALESCE(parent_pt.id, child_pt.id)'))
                    ->select(
                        \DB::raw('COALESCE(parent_pt.id, child_pt.id) as parent_id'),
                        \DB::raw('COUNT(products.product_id) as product_count')
                    );

                Log::info('Product Type Map Query Result: ', ['query' => $productTypeMapQuery->toSql(), 'bindings' => $productTypeMapQuery->getBindings()]);
                $productTypeMapQueryData = $productTypeMapQuery->get();

                $productTypeMap = [];
                foreach ($productTypeMapQueryData as $ptm) {
                    if ($ptm->product_count <= 0) {
                        continue;
                    }
                    $productTypeMap[$ptm->parent_id] = $ptm->product_count;
                }

                $device = Device::where('id', $request->device_id)->firstOrFail();

                $finalLinkArray['final_title'] = $device->brand->name . ' ' . $device->name . ' Accessories';
                foreach ($links as $link) {
                    // skip product types which do not have products for this device
                    if (!isset($productTypeMap[$link->product_type_id])) {
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

    /**
     * The handle Shopify gives a page created from this title. Every run of non-alphanumeric
     * characters becomes one hyphen (so "SGH-I987" stays "sgh-i987"), unlike the collection
     * handle generator in ShopifyBrandPageSyncJob, which drops them.
     */
    public static function pageHandle(?string $title): string
    {
        $handle = mb_strtolower((string)$title, 'UTF-8');

        // Normalize accented characters (e.g., é → e)
        $handle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $handle);

        // Apostrophes and ampersands are dropped, not turned into hyphens
        $handle = str_replace(["'", '&'], '', $handle);

        $handle = preg_replace('/[^a-z0-9]+/', '-', $handle);

        return trim($handle, '-');
    }
}
