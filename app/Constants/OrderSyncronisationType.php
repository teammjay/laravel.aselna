<?php

namespace App\Constants;

class OrderSyncronisationType
{
//ORDER(0),
//RETURN(1),
//CANCEL(2),
//REFUND(3);

    const ORDER = 0;
    const RETURN = 1;
    const CANCEL = 2;
    const REFUND = 3;

    const names = [
        self::ORDER => 'Order',
        self::RETURN => 'Return',
        self::CANCEL => 'Cancel',
        self::REFUND => 'Refund',
    ];

    public static function getOrderSyncronisationTypeName($orderStatusId)
    {
        return self::names[$orderStatusId];
    }

    public static function getOrderSyncronisationTypeId($orderStatusName)
    {
        return array_search($orderStatusName, self::names);
    }
}
