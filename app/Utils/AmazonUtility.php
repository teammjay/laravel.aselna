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
            case 'ELECTRONIC_ACCESSORY':
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
}
