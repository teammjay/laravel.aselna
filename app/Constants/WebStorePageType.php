<?php

namespace App\Constants;

class WebStorePageType
{
//    ALL_PRODUCT_TYPES(10),
//    PRODUCT_TYPE_BRANDS(20),
//    PRODUCT_TYPE_BRAND_DEVICES(30),
//    ALL_BRANDS(40),
//    BRAND_DEVICES(50),
//    DEVICE(60),
//    BRAND(70),
//    PRODUCT_TYPE(80),
//    BRAND_DEVICE_PRODUCT_TYPES(90),
//    DEVICE_PRODUCT_TYPE(100),
//    ALL_DEVICES(110);
    const ALL_PRODUCT_TYPES = 10;
    const PRODUCT_TYPE_BRANDS = 20;
    const PRODUCT_TYPE_BRAND_DEVICES = 30;
    const ALL_BRANDS = 40;
    const BRAND_DEVICES = 50;
    const DEVICE = 60;
    const BRAND = 70;
    const PRODUCT_TYPE = 80;
    const BRAND_DEVICE_PRODUCT_TYPES = 90;
    const DEVICE_PRODUCT_TYPE = 100;
    const ALL_DEVICES = 110;

    public static function getPageTypes(): array
    {
        return [
            self::ALL_PRODUCT_TYPES,
            self::PRODUCT_TYPE_BRANDS,
            self::PRODUCT_TYPE_BRAND_DEVICES,
            self::ALL_BRANDS,
            self::BRAND_DEVICES,
            self::DEVICE,
            self::BRAND,
            self::PRODUCT_TYPE,
            self::BRAND_DEVICE_PRODUCT_TYPES,
            self::DEVICE_PRODUCT_TYPE,
            self::ALL_DEVICES,
        ];
    }

    public static function getPageTypeName(int $type): string
    {
        $types = [
            self::ALL_PRODUCT_TYPES => 'All Product Types',
            self::PRODUCT_TYPE_BRANDS => 'Product Type Brands',
            self::PRODUCT_TYPE_BRAND_DEVICES => 'Product Type Brand Devices',
            self::ALL_BRANDS => 'All Brands',
            self::BRAND_DEVICES => 'Brand Devices',
            self::DEVICE => 'Device',
            self::BRAND => 'Brand',
            self::PRODUCT_TYPE => 'Product Type',
            self::BRAND_DEVICE_PRODUCT_TYPES => 'Brand Device Product Types',
            self::DEVICE_PRODUCT_TYPE => 'Device Product Type',
            self::ALL_DEVICES => 'All Devices',
        ];

        return $types[$type] ?? 'Unknown';
    }
}
