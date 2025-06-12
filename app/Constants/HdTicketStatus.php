<?php

namespace App\Constants;

class HdTicketSatus
{
    const NEEDS_ACTION = 0;
    const AWAITING_CUSTOMER = 1;
    const CLOSED = 2;
    const DELETED = 3;
    const SPAM = 4;

    const names = [
        self::NEEDS_ACTION => 'Needs Action',
        self::AWAITING_CUSTOMER => 'Awaiting Customer',
        self::CLOSED => 'Closed',
        self::DELETED => 'Deleted',
        self::SPAM => 'Spam'
    ];

    public static function getTicketStatus($status)
    {
        if (!array_key_exists($status, self::names)) {
            return null;
        }
        return self::names[$status];
    }

    // create a function to get id from name
    public static function getTicketStatusId($status)
    {
        return array_search($status, self::names);
    }
}
