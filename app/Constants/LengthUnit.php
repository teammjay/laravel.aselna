<?php

namespace App\Constants;

class LengthUnit
{
    const INCH = 1;
    const MILLIMETER = 2;
    const CENTIMETER = 3;
    const METER = 4;
    const FEET = 5;

    const names = [
        self::INCH => 'Inches',
        self::MILLIMETER => 'Millimeters',
        self::CENTIMETER => 'Centimeters',
        self::METER => 'Meters',
        self::FEET => 'Feet',
    ];

    const amazonNames = [
        self::INCH => 'inches',
        self::MILLIMETER => 'millimeters',
        self::CENTIMETER => 'centimeters',
        self::METER => 'meters',
        self::FEET => 'feet',
    ];

    private static $ratios = [
        [1, 25.4, 2.54, 0.0254, 0.0833333],
        [0.0393701, 1, 0.1, 0.001, 0.00328084],
        [0.3937008, 10, 1, 0.01, 0.0328084],
        [39.37008, 1000, 100, 1, 3.28084],
        [12, 304.8, 30.48, 0.3048, 1]
    ];

    public static function convert($fromUnit, $toUnit, $value)
    {
        return $value * self::$ratios[$fromUnit - 1][$toUnit - 1];
    }

    public function convertTo($toUnit, $value)
    {
        return self::convert($this->id, $toUnit, $value);
    }

    public static function getUnitName($unitId)
    {
        return self::names[$unitId];
    }

    public static function getAmazonUnitName($unitId)
    {
        return self::amazonNames[$unitId];
    }
}
