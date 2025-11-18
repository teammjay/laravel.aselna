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
                'unit' => LengthUnit::getAmazonUnitName(5),
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
        $combinedDeviceName = '';
        foreach ($itemDetail->devices as $device) {
            $deviceName = $device->device->brand->name . ' ' . $device->device->name;

            if (strlen($combinedDeviceName . ', ' . $deviceName) > 2000) {
                if ($count < 3) {
                    $count++;;
                    $compatible_phones[] = ['value' => ltrim($combinedDeviceName, ', '), 'language_tag' => 'en_US'];
                    $combinedDeviceName = '';
                } else break;
            } else {
                $combinedDeviceName .= ', ' . $deviceName;
            }
        }

        if (!empty($combinedDeviceName)) {
            $compatible_phones[] = ['value' => ltrim($combinedDeviceName, ', '), 'language_tag' => 'en_US'];
        }

//        foreach ($itemDetail->devices as $device) {
//            if ($maxCount > 0 && $index >= $maxCount) {
//                break;
//            }
//            $deviceDetail = $device->device;
//
//            $compatible_phones[] = [
//                'language_tag' => 'en_US',
//                'value' => self::getFullDeviceName($deviceDetail),
//            ];
//            $index++;
//        }
        return $compatible_phones;
    }

    public static function getSpecialFeatures($productData,$storeProduct)
    {
        $special_features = ProductSpecification::where('product_id', $productData->product_id)
            ->where('store_type_id', $storeProduct->store->store_type_id)
            ->get()
            ->map(function ($specification) {
                return $specification->name . ' : ' . $specification->calculated_value();
            })
            ->implode(', ');
        return [['value' => $special_features, 'language_tag' => 'en_US']];
    }

    public static function getFullDeviceName($deviceDetail)
    {
        if ($deviceDetail->brand->name == 'Apple' || $deviceDetail->brand->name == 'Google') {
            return $deviceDetail->name;
        }
        return $deviceDetail->brand->name . ' ' . $deviceDetail->name;
    }

    public static function getItemHardness(mixed $productData)
    {
        return [[
            'value' => '9H Hardness',
            'language_tag' => 'en_US',
        ]];
    }

}
