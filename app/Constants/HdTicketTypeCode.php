<?php

namespace App\Constants;

class HdTicketTypeCode
{
    const ORDER_INFORMATION = 0;
    const RETURN_REQUEST = 1;
    const ORDER_CANCELLATION_REQUEST = 2;
    const ORDER_REFUND_REQUEST = 3;
    const CUSTOMER_SERVICE = 5;
    const SHIPPING = 6;
    const MARKETPLACE_NOTIFICATION = 8;
    const FEEDBACK = 9;
    const ESCALATION = 10;
    const ITEM_NOT_RECEIVED = 11;

    const names = [
        self::ORDER_INFORMATION => 'Order Information',
        self::RETURN_REQUEST => 'Return Request',
        self::ORDER_CANCELLATION_REQUEST => 'Order Cancellation Request',
        self::ORDER_REFUND_REQUEST => 'Refund Request',
        self::CUSTOMER_SERVICE => 'Customer Service',
        self::SHIPPING => 'Shipping inquery',
        self::MARKETPLACE_NOTIFICATION => 'Marketplace Notification',
        self::FEEDBACK => 'Feedback',
        self::ESCALATION => 'Escalation',
        self::ITEM_NOT_RECEIVED => 'Item not received',
    ];

    public static function getTicketTypeCode($typeCodeId)
    {
        if (!array_key_exists($typeCodeId, self::names)) {
            return null;
        }
        return self::names[$typeCodeId];
    }

    // create a function to get id from name
    public static function getTicketTypeCodeId($typeCode)
    {
        return array_search($typeCode, self::names);
    }
}
