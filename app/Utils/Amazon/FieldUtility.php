<?php

namespace App\Utils\Amazon;

use App\Constants\LengthUnit;
use App\Constants\WeightUnit;
use Illuminate\Support\Facades\Log;

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

    public static function getComaptiblePhoneModels($itemDetail)
    {
        $compatible_phones = [];
        foreach ($itemDetail->devices as $device) {
            $deviceDetail = $device->device;
            Log::info("Device :: " . json_encode($deviceDetail));
            $compatible_phones[] = [
                'language_tag' => 'en_US',
                'value' => $deviceDetail->brand->name . ' ' . $deviceDetail->name,
            ];
        }
        return $compatible_phones;
    }

    public static function getItemHardness(mixed $productData)
    {
        return [[
            'value' => '9H',
            'language_tag' => 'en_US',
        ]];
    }

}
