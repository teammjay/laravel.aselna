<?php

namespace App\Constants;

class Operators
{
    const NONE = 0;
    const LESS_THAN = 1;
    const LESS_THAN_OR_EQUAL = 2;
    const GREATED_THAN = 3;
    const GREATED_THAN_OR_EQUAL = 4;
    const BETWEEN = 5;
    const EQUAL = 6;
    const TRUE_FALSE = 7;

    const names = [
        self::NONE => 'None',
        self::LESS_THAN => '<',
        self::LESS_THAN_OR_EQUAL => '<=',
        self::GREATED_THAN => '>',
        self::GREATED_THAN_OR_EQUAL => '>=',
        self::BETWEEN => 'Between',
        self::EQUAL => '=',
        self::TRUE_FALSE => '<>',
    ];

    public static function getOperatorsName($Id)
    {
        return self::names[$Id];
    }
}
