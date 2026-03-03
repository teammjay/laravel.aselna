<?php

namespace App\Constants;

class OrderSyncStatus
{

    const Created = 0;
    const LabelCreated = 1;
    const Error = 2;
    const LabelPrinted = 3;
    const Estimated = 4;
    const Accepted = 6;
    const InTransit = 7;
    const Delivered = 8;
    const DeliveryException = 9;
    const ReturningToSender = 10;
    const ReturnedToSender = 11;
    const Canceled = 12;
    const MarkAsShipped = 13;
    const Requested = 50;
    const Completed = 51;
    const Rejected = 55;
    const ESCALATED = 100;
    const PARTIAL_REFUND_OFFERED = 101;
    const PARTIAL_REFUND_ACCEPTED = 102;
    const PARTIAL_REFUND_DECLINED = 103;
    const REFUNDED = 104;
    const REPLACEMENT_OFFERED = 105;
    const REPLACEMENT_OFFER_ACCEPTED = 106;
    const REPLACEMENT_OFFER_DECLINED = 107;
    const REPLACEMENT_SHIPPED = 108;
    const REPLACEMENT_DELIVERED = 109;
    const REFUND_ERROR = 110;
    const PENDING = 111;

    const names = [
        self::Created => 'Created',
        self::LabelCreated => 'Label Created',
        self::Error => 'Error',
        self::LabelPrinted => 'Label Printed',
        self::Estimated => 'Estimated',
        self::Accepted => 'Accepted',
        self::InTransit => 'In Transit',
        self::Delivered => 'Delivered',
        self::DeliveryException => 'Delivery Exception',
        self::ReturningToSender => 'Returning To Sender',
        self::ReturnedToSender => 'Returned To Sender',
        self::Canceled => 'Canceled',
        self::MarkAsShipped => 'Mark As Shipped',
        self::Requested => 'Requested',
        self::Completed => 'Completed',
        self::Rejected => 'Rejected',
        self::ESCALATED => 'ESCALATED',
        self::PARTIAL_REFUND_OFFERED => 'PARTIAL REFUND OFFERED',
        self::PARTIAL_REFUND_ACCEPTED => 'PARTIAL REFUND ACCEPTED',
        self::PARTIAL_REFUND_DECLINED => 'PARTIAL REFUND DECLINED',
        self::REFUNDED => 'REFUNDED',
        self::REPLACEMENT_OFFERED => 'REPLACEMENT OFFERED',
        self::REPLACEMENT_OFFER_ACCEPTED => 'REPLACEMENT OFFER ACCEPTED',
        self::REPLACEMENT_OFFER_DECLINED => 'REPLACEMENT OFFER DECLINED',
        self::REPLACEMENT_SHIPPED => 'REPLACEMENT SHIPPED',
        self::REPLACEMENT_DELIVERED => 'REPLACEMENT DELIVERED',
        self::REFUND_ERROR => 'REFUND ERROR',
        self::PENDING => 'PENDING',
    ];

    public static function getOrderSyncStatusName($orderStatusId)
    {
        return self::names[$orderStatusId] ?? '';
    }

    // create a function to get id from name
    public static function getOrderSyncStatusId($orderStatusName)
    {
        return array_search($orderStatusName, self::names);
    }
}
