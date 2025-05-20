<?php

namespace App\Constants;

class OrderOperationType
{

    const OrderShipment = 0;
    const OrderCancellation = 1;
    const OrderReturn = 2;
    const OrderRefund = 3;
    const OrderReturnRefund = 4;
    const OrderReplacement = 6;
    const LabelShipment = 8;
    const OrderNotReceived = 9;

    const names = [
        self::OrderShipment => 'OrderShipment',
        self::OrderCancellation => 'OrderCancellation',
        self::OrderReturn => 'OrderReturn',
        self::OrderRefund => 'OrderRefund',
        self::OrderReturnRefund => 'OrderReturnRefund',
        self::OrderReplacement => 'OrderReplacement',
        self::LabelShipment => 'LabelShipment',
        self::OrderNotReceived => 'OrderNotReceived'
    ];

    public static function getOrderOperationTypeName($orderStatusId)
    {
        return self::names[$orderStatusId];
    }

    // create a function to get id from name
    public static function getOrderOperationTypeId($orderStatusName)
    {
        return array_search($orderStatusName, self::names);
    }

    public static function isReturn($type)
    {
        return $type === self::OrderReturn;
    }

    public static function isShipment($type)
    {
        return $type === self::OrderShipment;
    }

    public static function isRefund($type)
    {
        return $type === self::OrderRefund || $type === self::OrderReturnRefund;
    }
}
