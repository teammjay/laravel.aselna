<?php

namespace App\Utils;

use App\Constants\LengthUnit;
use App\Constants\WeightUnit;
use App\Models\ListingRequirement;
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
            case 'PHONE_ACCESSORY':
                return self::getPhoneAccessory($itemDetail);
            case 'ELECTRONIC_ADAPTER':
                return self::getElectronicAdapterFields($itemDetail);
            case 'CABLE_OR_ADAPTER':
                return self::getCableOrAdapterFields($itemDetail);
            case 'INPUT_PEN':
                return self::getInputPenFields($itemDetail);
            case 'MULTIPORT_HUB':
                return self::getMultiportHubFields($itemDetail);
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        // get value of items included
        $items_included = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Items Included')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connectivity_technology')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connector_gender = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_gender')
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

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
            ->where('name', 'connectivity_technology')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $form_factor = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'headphones_form_factor')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $EarPlacement = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'headphones_ear_placement')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'headphones_ear_placement' => [['value' => $EarPlacement->calculated_value()]],
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'mounting_type')
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        $number_of_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connectivity_technology')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $outPutVoltage = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'output_voltage_value')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $inputVoltage = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'input_voltage_value')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $outputCurrent = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'output_current_value')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();


        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'connector_type' => [['value' => $connectivity->calculated_value(), 'language_tag' => 'en_US']],
            'connectivity_technology' => [['value' => 'USB', 'language_tag' => 'en_US']],
            'number_of_ports' => [['value' => $number_of_ports->calculated_value()]],
            'output_current' => [['value' => $outputCurrent->calculated_value(), 'unit' => 'amps']],
            'input_voltage' => [['value' => $inputVoltage->calculated_value(), 'unit' => 'volts']],
            'special_feature' => [['value' => $special_features, 'language_tag' => 'en_US']],
            'compatible_phone_models' => FieldUtility::getComaptiblePhoneModels($itemDetail),
            'output_voltage' => [['value' => $outPutVoltage->calculated_value(), 'unit' => 'volts']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
        ];
    }

    private static function getPowerBankFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;

        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        $number_of_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connectivity = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connectivity_technology')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $power_source_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'power_source_type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $recommended_use = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'recommended_uses_for_product')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $outPutVoltage = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'output_voltage_value')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'recommended_uses_for_product' => [['value' => $recommended_use->calculated_value(), 'language_tag' => 'en_US']],
            'power_source_type' => [['value' => $power_source_type->calculated_value(), 'language_tag' => 'en_US']],
            'connector_type' => [['value' => $connectivity->calculated_value(), 'language_tag' => 'en_US']],
            'item_length_width_thickness' => [[
                'length' => ['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName(1)],
                'width' => ['value' => $productData->dimensions_width, 'unit' => LengthUnit::getAmazonUnitName(1)],
                'thickness' => ['value' => '1', 'unit' => 'inches'],
            ]],
            'number_of_ports' => [[
                'value' => $number_of_ports->calculated_value()
            ]],
            'voltage' => [['value' => $outPutVoltage->calculated_value(), 'unit' => 'volts']],
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
            'maximum_height' => [['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName(1)]],
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        $form_factor = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'form_factor')
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
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');

        $mounting_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'mounting_type')
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

    private static function getPhoneAccessory($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);

        return [

        ];
    }

    private static function getElectronicAdapterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);

        $connector_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'connector_type' => [['value' => $connector_type->calculated_value(), 'language_tag' => 'en_US']]
        ];
    }

    private static function getCableOrAdapterFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $material = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Material')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connector_gender = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_gender')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connector_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
            'material' => [['value' => $material->calculated_value(), 'language_tag' => 'en_US']],
            'connector_gender' => [['value' => $connector_gender->calculated_value()]],
            'cable' => FieldUtility::getCableFieldValue($productData),
            'connector_type' => [['value' => $connector_type->calculated_value(), 'language_tag' => 'en_US']]
        ];
    }

    private static function getInputPenFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);
        $connector_gender = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_gender')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $connector_type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'connector_type')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
            'cable' => FieldUtility::getCableFieldValue($productData),
            'connector_type' => [['value' => $connector_type->calculated_value(), 'language_tag' => 'en_US']],
            'connector_gender' => [['value' => $connector_gender->calculated_value()]],
        ];
    }

    private static function getMultiportHubFields($itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $productData = $itemDetail->storeProduct->product;
        Log::info('Product Id : ' . $productData->id);

        $items_count = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Number of Items')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $warranty = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Warranty')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $total_usb_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'total_usb_ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $special_feature = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'special_feature')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $data_transfer_rate = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'data_transfer_rate')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $hardware_interface = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'hardware_interface')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        $number_of_ports = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'number_of_ports')
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();

        return [
            'number_of_items' => [['value' => $items_count->calculated_value()]],
            'warranty_description' => [['value' => $warranty->calculated_value(), 'language_tag' => 'en_US']],
            'supplier_declared_dg_hz_regulation' => [['value' => 'not_applicable']],
            'total_usb_ports' => [['value' => $total_usb_ports->calculated_value()]],
            'special_feature' => [['value' => $special_feature->calculated_value(), 'language_tag' => 'en_US']],
            'data_transfer_rate' => [[
                'value' => $data_transfer_rate->calculated_value(),
                'unit' => 'megabytes_per_second'
            ]],
            'hardware_interface' => [['value' => $hardware_interface->calculated_value()]],
            'number_of_ports' => [['value' => $number_of_ports->calculated_value()]]
        ];
    }

    public static function getFieldsForProductTypesV2($marketplace_code, $itemDetail)
    {
        $storeProduct = $itemDetail->storeProduct;
        $requirements = ListingRequirement::where('category', $marketplace_code)
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get();

        $requirementValues = [];

        foreach ($requirements as $requirement) {
            if (!isset($requirement->key_value)) {
                continue;
            }
            $valueArray = self::getRequirementValue($requirement, $itemDetail, $storeProduct);
            if ($requirement->is_common == 1 && empty($valueArray)) {
                continue;
            }
            $requirementValues[$requirement->key_value] = $valueArray;
        }
        return $requirementValues;
    }

    public static function getRequirementValue(mixed $requirement, mixed $itemDetail, mixed $storeProduct)
    {
        $productData = $storeProduct->product;
        $value = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', $requirement->key_value)
            ->where('store_type_id', $storeProduct->store->store_type_id)->first();
        switch ($requirement->key_value) {
            case 'compatible_phone_models' :
                return FieldUtility::getComaptiblePhoneModels($itemDetail);
            case 'cable':
                return FieldUtility::getCableFieldValue($productData);
            case 'item_package_weight':
                return FieldUtility::getItemPackageWeight($productData);
            case 'special_feature':
                return FieldUtility::getSpecialFeatures($productData, $storeProduct);
            case 'item_hardness':
                return FieldUtility::getItemHardness($productData);
            case 'item_length_width':
                return [[
                    'length' => ['value' => $productData->package_dimensions_length, 'unit' => 'inches'],
                    'width' => ['value' => $productData->package_dimensions_width, 'unit' => 'inches'],
                ]];
            case 'color':
                return [['value' => $productData->color->name, 'language_tag' => 'en_US']];
            case 'data_transfer_rate':
                if ($value) {
                    return [[
                        'value' => $value->calculated_value(),
                        'unit' => 'megabytes_per_second'
                    ]];
                }
                break;
            case 'item_length_width_thickness':
                return [[
                    'length' => ['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName(1)],
                    'width' => ['value' => $productData->dimensions_width, 'unit' => LengthUnit::getAmazonUnitName(1)],
                    'thickness' => ['value' => '1', 'unit' => 'inches'],
                ]];
            case 'maximum_height':
                return [['value' => $productData->dimensions_length, 'unit' => LengthUnit::getAmazonUnitName(1)]];
            case 'input_voltage':
            case 'output_voltage':
                if ($value) {
                    return [['value' => $value->calculated_value(), 'unit' => 'volts']];
                }
                break;
            case 'model_name':
            case 'model_number':
                return [['value' => $itemDetail->sku, 'language_tag' => 'en_US']];
            default:
                if ($value) {
                    return [['value' => $value->calculated_value(), 'language_tag' => 'en_US']];
                }
        }
        return [];
    }
}
