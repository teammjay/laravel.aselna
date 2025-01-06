<?php

namespace App\Constants;

class FeedStatus
{
    const CREATED = 0;
    const PROCESSING = 1;
    const ERROR = 2;
    const FAILED = 3;
    const PROCESSED = 4;

    const names = [
        self::CREATED => 'Created',
        self::PROCESSING => 'Processing',
        self::ERROR => 'Error',
        self::FAILED => 'Failed',
        self::PROCESSED => 'Processed',
    ];

    public static function getStatusName($statusId)
    {
        return self::names[$statusId];
    }
}
