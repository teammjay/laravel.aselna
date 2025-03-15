<?php

namespace App\Constants;

class PageTypes
{
    const SHIPPING = 2;
    const RETURNS = 3;
    const PAYMENTS = 4;
    const FEEDBACK = 8;
    const RETURN_POLICY = 10;

    const names = [
        self::SHIPPING => 'Shipping',
        self::RETURNS => 'Returns',
        self::PAYMENTS => 'Payments',
        self::FEEDBACK => 'Feedback',
        self::RETURN_POLICY => 'Return Policy',
    ];

    public static function getPageTypeName($pageTypeId)
    {
        return self::names[$pageTypeId];
    }
}
