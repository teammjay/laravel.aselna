<?php

namespace App\Constants;

class FeedStatus
{
    const CREATED = 0;
    const PROCESSING = 1;
    const ERROR = 2;
    const FAILED = 3;
    const PROCESSED = 4;
    const SUBMITTED = 5;

    const names = [
        self::CREATED => 'Created',
        self::PROCESSING => 'Processing',
        self::ERROR => 'Error',
        self::FAILED => 'Failed',
        self::PROCESSED => 'Processed',
        self::SUBMITTED => 'Submitted',
    ];

    const badgeClasses = [
        self::CREATED => 'badge-secondary',
        self::PROCESSING => 'badge-info',
        self::ERROR => 'badge-danger',
        self::FAILED => 'badge-danger',
        self::PROCESSED => 'badge-success',
        self::SUBMITTED => 'badge-warning',
    ];

    public static function getStatusName($statusId)
    {
        return self::names[$statusId] ?? 'Unknown';
    }

    public static function getStatusBadgeClass($statusId)
    {
        return self::badgeClasses[$statusId] ?? 'badge-secondary';
    }

    /**
     * Check if a status is terminal (no further processing expected).
     */
    public static function isTerminal($statusId): bool
    {
        return in_array($statusId, [self::PROCESSED, self::ERROR, self::FAILED]);
    }
}