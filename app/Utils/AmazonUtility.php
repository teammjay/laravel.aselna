<?php

namespace App\Utils;

use App\Constants\LengthUnit;
use App\Constants\WeightUnit;
use App\Models\ProductSpecification;
use App\Utils\Amazon\FieldUtility;
use Illuminate\Support\Facades\Log;

class AmazonUtility
{
    public static function getFieldsForProductTypes($productType, $itemDetail)
    {
        switch ($productType) {
            case 'ELECTRONIC_CABLE':
                return self::getElectronicCableFields($itemDetail);
            case 'SCREEN_PROTECTOR':
                return self::getScreenProtectorsFeildType($itemDetail);
            case 'HEADPHONES':
                return self::getHeadphonesFields($itemDetail);
            case 'PORTABLE_ELECTRONIC_DEVICE_MOUNT':
                return self::getPortableElectronicDeviceMountFields($itemDetail);
            case 'CELL_PHONE_HOLSTER' :
                return self::getCellPhoneHolsterFields($itemDetail);
            case 'CHARGING_ADAPTER':
                return self::getChargingAdapterFields($itemDetail);
            case 'POWER_BANK':
                return self::getPowerBankFields($itemDetail);
            case 'CAMERA_TRIPOD':
                return self::getCameraTripodFields($itemDetail);
            case 'PORTABLE_ELECTRONIC_DEVICE_ARMBAND':
                return self::getPortableElectronicDeviceArmbandFields($itemDetail);
            case 'PORTABLE_ELECTRONIC_DEVICE_STAND':
                return self::getPortableElectronicDeviceStandFields($itemDetail);
        }
    }

    private static function getElectronicCableFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);
        // combine the name and value into this string -> name : value, name : value
        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        return [
            'cable' => FieldUtility::getCableFieldValue($productData),
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'number_of_items' => [['value' => 0]],
            'connector_gender' => [['value' => 'Female-to-Female']],
            'connector_type' => [['value' => 'USB Type C']],
            'included_components' => [['value' => 'Cable', 'language_tag' => 'en_US']],
            'compatible_devices' => [['value' => 'Smartphone', 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features ?? 'Cables', 'language_tag' => 'en_US']],
        ];
    }

    private static function getScreenProtectorsFeildType($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        return [
            'finish_type' => [['value' => $productData->material ?? 'Glossy', 'language_tag' => 'en_US']],
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'included_components' => [['value' => 'Cover', 'language_tag' => 'en_US']],
            'compatible_devices' => [['value' => 'Cellphone', 'language_tag' => 'en_US']],
            'item_hardness' => FieldUtility::getItemHardness($productData),
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'material' => [['value' => $productData->material ?? 'Glossy', 'language_tag' => 'en_US']],
            'color' => [['value' => $productData->color->name ?? 'Black', 'language_tag' => 'en_US']],
            'item_length_width' => [[
                'length' => ['value' => $productData->package_dimensions_length, 'unit' => 'inches'],
                'width' => ['value' => $productData->package_dimensions_width, 'unit' => 'inches'],
            ]]
        ];
    }

    private static function getHeadphonesFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        return [
            'headphones_ear_placement' => [['value' => 'in_ear']],
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'color' => [['value' => $productData->color->name ?? 'Black', 'language_tag' => 'en_US']],
            'connectivity_technology' => [['value' => 'Wireless', 'language_tag' => 'en_US',]],
            'headphones_form_factor' => [['value' => 'In Ear', 'language_tag' => 'en_US',]],
            'model_name' => FieldUtility::getComaptiblePhoneModels($itemDetail, 1),
            'included_components' => [['value' => 'Heaphones', 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
            'model_number' => [['value' => $itemDetail->sku]],
        ];
    }

    private static function getPortableElectronicDeviceMountFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Mounting Type')
            ->first();

        return [
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'compatible_devices' => [['value' => 'Smartphones', 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'material' => [['value' => $productData->material ?? 'Glossy', 'language_tag' => 'en_US']],
            'mounting_type' => [['value' => $mounting_type->value ?? 'Dashboard', 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
            'included_components' => [['value' => 'Mount', 'language_tag' => 'en_US']],
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
        ];
    }

    private static function getCellPhoneHolsterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        return [
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'included_components' => [['value' => 'Cell Phone Hosteler', 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'fastener' => [['type' => [['value' => 'Magnetic', 'language_tag' => 'en_US',]]]],
            'number_of_items' => [['value' => 0]],
        ];
    }

    private static function getChargingAdapterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        return [
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
            'connector_type' => '',
            'connectivity_technology' => '',
            'number_of_ports' => '',
            'output_current' => '',
            'compatible_devices' => '',
            'input_voltage' => '',
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'output_voltage' => '',
        ];
    }

    private static function getPowerBankFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        return [
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'recommended_uses_for_product' => '',
            'power_source_type' => '',
            'connector_type' => '',
            'item_length_width_thickness' => '',
            'number_of_ports' => '',
            'voltage' => '',
            'battery' => '',
            'compatible_devices' => '',
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
        ];
    }

    private static function getCameraTripodFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        return [
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'compatible_devices' => [['value' => 'Smartphones', 'language_tag' => 'en_US']],
            'maximum_height' => [['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName($productData->dimensions_length_unit ?? 1)]],
            'number_of_items' => [['value' => 0]],
            'material' => [['value' => $productData->material ?? 'Glossy', 'language_tag' => 'en_US']],
            'included_components' => [['value' => 'Tripod', 'language_tag' => 'en_US']],
        ];
    }

    private static function getPortableElectronicDeviceArmbandFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        return [
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'compatible_devices' => [['value' => 'Smartphones', 'language_tag' => 'en_US']],
            'form_factor' => [['value' => 'Pouch', 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
        ];
    }

    private static function getPortableElectronicDeviceStandFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->value;
            })
            ->implode(', ');
        if (isset($special_features) || empty(trim($special_features))) {
            $special_features = 'Hard Screen Protectors';
        }

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Mounting Type')
            ->first();

        return [
            'warranty_description' => [['value' => 'No Warranty Included', 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'included_components' => [['value' => 'Cell Phone Hosteler', 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => 0]],
            'material' => [['value' => $productData->material ?? 'Glossy', 'language_tag' => 'en_US']],
            'mounting_type' => [['value' => $mounting_type->value ?? 'Dashboard', 'language_tag' => 'en_US']],
            'compatible_devices' => [['value' => 'Smartphones', 'language_tag' => 'en_US']],
        ];
    }
}
