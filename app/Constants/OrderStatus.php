<?php

namespace App\Constants;

class OrderStatus
{
// convert this into a constant
//    Pending(1),
//    Ready(2),
//    Acknowledged(3), // used to be processing
//    Shipped(4),
//    Canceled(5),
//    /**
//     * Order list retrieval creates order records without details. The details of each order is retrived by a separate service.
//     * until details are retrieved order is in Synchronizing state.
//     */
//    Synchronizing(6),
//    PartiallyShipped(7),
//    SynchronizatonFailed(8),
//    Refund(9),
//    Replaced(10);

    const Pending = 1;
    const Ready = 2;
    const Acknowledged = 3;
    const Shipped = 4;
    const Canceled = 5;
    const Synchronizing = 6;
    const PartiallyShipped = 7;
    const SynchronizatonFailed = 8;
    const Refund = 9;
    const Replaced = 10;

    const names = [
        self::Pending => 'Pending',
        self::Ready => 'Ready',
        self::Acknowledged => 'Acknowledged',
        self::Shipped => 'Shipped',
        self::Canceled => 'Canceled',
        self::Synchronizing => 'Synchronizing',
        self::PartiallyShipped => 'Partially Shipped',
        self::SynchronizatonFailed => 'Synchronizaton Failed',
        self::Refund => 'Refund',
        self::Replaced => 'Replaced',
    ];

    public static function getOrderStatusName($orderStatusId)
    {
        return self::names[$orderStatusId];
    }

    // create a function to get id from name
    public static function getOrderStatusId($orderStatusName)
    {
        return array_search($orderStatusName, self::names);
    }
}
