<?php

namespace App\Constants;

class WeightUnit
{
    const OZ = 0;
    const LB = 1;
    const G = 2;
    const KG = 3;
    const MG = 4;

    const names = [
        self::OZ => 'Ounces',
        self::LB => 'Pounds',
        self::G => 'Grams',
        self::KG => 'Kilograms',
        self::MG => 'Milligrams',
    ];

    const unitnames = [
        self::OZ => 'Oz',
        self::LB => 'Lb',
        self::G => 'G',
        self::KG => 'Kg',
        self::MG => 'Mg',
    ];

    const amazonNames = [
        self::OZ => 'ounces',
        self::LB => 'pounds',
        self::G => 'grams',
        self::KG => 'kilograms',
        self::MG => 'milligrams',
    ];

    private static $ratios = [
        [1, 0.0625, 28.3495, 0.0283495, 28349.5000000105],
        [16, 1, 453.592, 0.453592, 453592.0000001679],
        [0.035274, 0.00220462, 1, 0.001, 1000],
        [35.274, 2.20462, 1000, 1, 1000000],
        [0.000035274, 0.00000220462, 0.001, 0.000001, 1]
    ];

    public static function convert($fromUnit, $toUnit, $value)
    {
        return $value * self::$ratios[$fromUnit][$toUnit];
    }

    public function convertTo($toUnit, $value)
    {
        return self::convert($this->id, $toUnit, $value);
    }

    public static function getUnitSelfName($unitId)
    {
        return self::unitnames[$unitId];
    }

    public static function getUnitName($unitId)
    {
        if (!isset(self::names[$unitId])) {
            return 'OZ';
        }
        return self::names[$unitId];
    }

    public static function getAmazonUnitName($unitId)
    {
        if (strtolower($unitId) == 'kg' || $unitId == 3) {
            $unitId = self::KG;
        } elseif (strtolower($unitId) == 'g' || $unitId == 2) {
            $unitId = self::G;
        } elseif (strtolower($unitId) == 'oz' || $unitId == 0) {
            $unitId = self::OZ;
        } elseif (strtolower($unitId) == 'lb' || $unitId == 1) {
            $unitId = self::LB;
        } elseif (strtolower($unitId) == 'mg' || $unitId == 4) {
            $unitId = self::MG;
        }
        return self::amazonNames[$unitId];
    }
}
