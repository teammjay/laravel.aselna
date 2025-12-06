<?php

namespace App\Constants;

class FeedTypes
{
    const CREATE = 1;
    const INVENTORY = 2;

    const PRICE = 3;

    const MANUAL_UPDATE = 4;

    const DELETE = 5;

    const IMAGE_UPDATE = 6;

    const RELATIONSHIP_UPDATE = 7;

    const SHIPMENT_UPDATE = 8;

    const ORDER_CANCEL = 9;

    const RESUBMIT = 10;

    const ITEM_REPORT = 50;

    const names = [
        self::CREATE => 'Create',
        self::INVENTORY => 'Inventory',
        self::PRICE => 'Price',
        self::MANUAL_UPDATE => 'Manual Update',
        self::DELETE => 'Delete',
        self::IMAGE_UPDATE => 'Image Update',
        self::RELATIONSHIP_UPDATE => 'Relationship Update',
        self::SHIPMENT_UPDATE => 'Shipment Update',
        self::ORDER_CANCEL => 'Order Cancel',
        self::ITEM_REPORT => 'Item Report',
        self::RESUBMIT => 'Re-submit',
    ];

    public static function getFeedName($feedId)
    {
        return self::names[$feedId];
    }
}
