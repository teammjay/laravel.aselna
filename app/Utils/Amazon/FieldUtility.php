<?php

namespace App\Utils\Amazon;

use App\Constants\LengthUnit;
use App\Constants\WeightUnit;

class FieldUtility
{
    public static function getCableFieldValue($productData)
    {
        return [[
            'length' => [[
                'decimal_value' => $productData->dimensions_length,
                'string_value' => $productData->dimensions_length . ' ' . LengthUnit::getAmazonUnitName($productData->dimensions_length_unit ?? 1),
                'unit' => LengthUnit::getAmazonUnitName($productData->dimensions_length_unit ?? 1),
            ]],
            'type' => [[
                'language_tag' => 'en_US',
                'value' => 'Lightning',
            ]]
        ]];
    }

    public static function getItemPackageWeight($productData)
    {
        return [[
            'unit' => WeightUnit::getAmazonUnitName($productData->package_weight_unit ?? 0),
            'value' => $productData->product_package_weight ?? 1,
        ]];
    }
}
