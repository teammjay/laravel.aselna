<?php

namespace App\Constants;

class ListingStatus
{

    const Active = 1;
    const Suspended = 2;
    const Deleted = 3;

    const names = [
        self::Active => 'Active',
        self::Suspended => 'Suspended',
        self::Deleted => 'Deleted'
    ];

    public static function getListingStatusName($listingStatusId)
    {
        return self::names[$listingStatusId];
    }

    // create a function to get id from name
    public static function getListingStatusId($listingStatusName)
    {
        return array_search($listingStatusName, self::names);
    }
}
