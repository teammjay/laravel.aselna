<?php

namespace App\Utils;

use App\Constants\WebStorePageType;
use App\Models\Brand;
use App\Models\Device;
use App\Models\ProductType;
use App\Models\StoreNavigationPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

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

            case WebStorePageType::PRODUCT_TYPE_BRAND_DEVICES:
                $device = Device::where('id', $request->device_id)->firstOrFail();
                $finalLinkArray['final_title'] = $device->name . ' for ' . $device->brand->name;
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->get();
                foreach ($links as $link) {
                    if ($link->device->order <= 10) {
                        $finalLinkArray['popular_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => true
                        ];
                    } else {
                        $finalLinkArray['other_link'][] = [
                            'id' => $link->id,
                            'title' => $link->device->name,
                            'imageUrl' => $link->store_image_url,
                            'url' => "/pages/" . $link->url_path,
                            'popular' => false
                        ];
                    }
                }
                break;

            case WebStorePageType::ALL_BRANDS:
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->get();
                $finalLinkArray['final_title'] = 'Shop By Brand';
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

            case WebStorePageType::BRAND_DEVICES:
                $finalLinkArray['final_title'] = '';
                break;

            case WebStorePageType::DEVICE:
                break;

            case WebStorePageType::BRAND:
                $brand = Brand::where('id', $request->device_brand_id)->firstOrFail();
                $links = StoreNavigationPage::where('store_id', $store_id)
                    ->where('page_type', WebStorePageType::BRAND_DEVICE_PRODUCT_TYPES)
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
                $finalLinkArray['final_title'] = '';
                foreach ($links as $link) {
                    $finalLinkArray['popular_link'][] = [
                        'id' => $link->id,
                        'title' => $link->productType->name,
                        'imageUrl' => $link->store_image_url,
                        'url' => "/pages/" . $link->url_path,
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
