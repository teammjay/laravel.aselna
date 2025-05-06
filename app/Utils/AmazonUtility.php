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
            case 'CELLULAR_PHONE_CASE':
                return self::getCellularPhoneCase($itemDetail);
        }
        return [];
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
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        // get value of items included
        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Connectivity')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connector_gender = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Connector Gender')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'cable' => FieldUtility::getCableFieldValue($productData),
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'connector_gender' => [['value' => $connector_gender->calculated_value()]],
            'connector_type' => [['value' => $connectivity ? $connectivity->calculated_value() : '']],
            'included_components' => [['value' => $items_included ? $items_included->calculated_value() : '', 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getScreenProtectorsFeildType($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'finish_type' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'item_hardness' => FieldUtility::getItemHardness($productData),
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'color' => [['value' => $productData->color->name, 'language_tag' => 'en_US']],
            'item_length_width' => [[
                'length' => ['value' => $productData->package_dimensions_length, 'unit' => 'inches'],
                'width' => ['value' => $productData->package_dimensions_width, 'unit' => 'inches'],
            ]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getHeadphonesFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Connectivity')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $form_factor = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Form Factor')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'headphones_ear_placement' => [['value' => $form_factor->calculated_value()]],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'color' => [['value' => $productData->color->name, 'language_tag' => 'en_US']],
            'connectivity_technology' => [['value' => $connectivity->calculated_value(), 'language_tag' => 'en_US',]],
            'headphones_form_factor' => [['value' => $form_factor->calculated_value(), 'language_tag' => 'en_US',]],
            'model_name' => [['value' => $itemDetail->sku, 'language_tag' => 'en_US']],
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'model_number' => [['value' => $itemDetail->sku]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getPortableElectronicDeviceMountFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Mounting Type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'mounting_type' => [['value' => $mounting_type->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getCellPhoneHolsterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $fasteners = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Fastening')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'item_package_weight' => FieldUtility::getItemPackageWeight($productData),
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'fastener' => [['type' => [['value' => $fasteners->calculated_value(), 'language_tag' => 'en_US',]]]],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getChargingAdapterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $number_of_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Connectivity')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();


        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'connector_type' => [['value' => $connectivity->calculated_value(), 'language_tag' => 'en_US']],
            'connectivity_technology' => [['value' => 'USB', 'language_tag' => 'en_US']],
            'number_of_ports' => [['value' => $number_of_ports->calculated_value()]],
            'output_current' => [['value' => $productData->output_current_value, 'unit' => $productData->output_current_unit]],
            'input_voltage' => [['value' => $productData->input_voltage_value, 'unit' => $productData->input_voltage_unit]],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'output_voltage' => [['value' => $productData->output_voltage_value, 'unit' => $productData->output_voltage_unit]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getPowerBankFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $number_of_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Connectivity')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $power_source_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Power Source type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $recommended_use = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Recommended Uses')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'recommended_uses_for_product' => [['value' => $recommended_use->calculated_value(), 'language_tag' => 'en_US']],
            'power_source_type' => [['value' => $power_source_type->calculated_value(), 'language_tag' => 'en_US']],
            'connector_type' => [['value' => $connectivity->calculated_value(), 'language_tag' => 'en_US']],
            'item_length_width_thickness' => [[
                'length' => ['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName($productData->dimensions_length_unit)],
                'width' => ['value' => $productData->dimensions_width, 'unit' => LengthUnit::getAmazonUnitName($productData->dimensions_width_unit)],
                'thickness' => ['value' => '1', 'unit' => 'inches'],
            ]],
            'number_of_ports' => [[
                'value' => $number_of_ports->calculated_value()
            ]],
            'voltage' => [['value' => $productData->output_voltage_value, 'unit' => $productData->output_voltage_unit]],
            'battery' => [['value' => '20000', 'unit' => 'amp_hours']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getCameraTripodFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'maximum_height' => [['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName($productData->dimensions_length_unit)]],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getPortableElectronicDeviceArmbandFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $form_factor = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Form Factor')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'form_factor' => [['value' => $form_factor->calculated_value(), 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getPortableElectronicDeviceStandFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->where('store_type_id',$storeProduct->store->store_type_id)->first();

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Mounting Type')
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->first();

        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'included_components' => [['value' => $items_included->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'mounting_type' => [['value' => $mounting_type->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getCellularPhoneCase($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'color' => [['value' => $productData->color->name, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
        ];
    }
}
