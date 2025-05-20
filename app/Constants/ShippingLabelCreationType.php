<?php

namespace App\Constants;

class ShippingLabelCreationType
{
    const NoLabel = 0;
    const CreateLabel = 1;
    const MarkAsShipped = 2;

    const names = [
        self::NoLabel => 'NoLabel',
        self::CreateLabel => 'CreateLabel',
        self::MarkAsShipped => 'MarkAsShipped',
    ];

    public static function getShippingLabelCreationTypeName($shippingLabelCreationTypeId)
    {
        return self::names[$shippingLabelCreationTypeId];
    }

    // create a function to get id from name
    public static function getShippingLabelCreationTypeId($shippingLabelCreationTypeName)
    {
        return array_search($shippingLabelCreationTypeName, self::names);
    }
}
