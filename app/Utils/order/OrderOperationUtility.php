<?php

namespace App\Utils\order;

class OrderOperationUtility
{
    public static function processShipment($orderOperation)
    {
        if (isset($orderOperation->order) ) {
            if (isset($orderOperation->order->contingent) && $orderOperation->order->contingent) {
//                $orderOperation->status =
            }
        }
    }
}
