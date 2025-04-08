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
}
