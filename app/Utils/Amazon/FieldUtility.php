<?php

namespace App\Utils\Amazon;

use App\Constants\LengthUnit;
use App\Constants\WeightUnit;
use App\Models\ProductSpecification;
use Illuminate\Support\Facades\Log;

class FieldUtility
{
    public static function getCableFieldValue($productData)
    {
        $type = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Type')
            ->first();

        $cable_length = ProductSpecification::where('product_id', $productData->product_id)
            ->where('name', 'Cable Length')
            ->first();

        return [[
            'length' => [[
//                'decimal_value' => 3,
                'string_value' => $cable_length->calculated_value(),
//                'unit' => LengthUnit::getAmazonUnitName(5),
            ]],
            'type' => [[
                'language_tag' => 'en_US',
                'value' => $type->calculated_value(),
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

    public static function getComaptiblePhoneModels($itemDetail, $maxCount = -1)
    {
        $compatible_phones = [];
        $index = 0;
        foreach ($itemDetail->devices as $device) {
            if ($maxCount > 0 && $index >= $maxCount) {
                break;
            }
            $deviceDetail = $device->device;
            Log::info("Device :: " . json_encode($deviceDetail));

            $compatible_phones[] = [
                'language_tag' => 'en_US',
                'value' => $deviceDetail->brand->name . ' ' . $deviceDetail->name,
            ];
            $index++;
        }
        return $compatible_phones;
    }

    function getFullDeviceName()
    {

    }

    public static function getItemHardness(mixed $productData)
    {
        return [[
            'value' => '9H Hardness',
            'language_tag' => 'en_US',
        ]];
    }

}
