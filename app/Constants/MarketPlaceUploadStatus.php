<?php

namespace App\Constants;

class MarketPlaceUploadStatus
{
    public const PENDING = 'pending';
    public const IN_PROGRESS = 'in_progress';
    public const SUCCESS = 'success';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';

    public static function getStatuses(): array
    {
        return [
            self::PENDING,
            self::IN_PROGRESS,
            self::SUCCESS,
            self::FAILED,
            self::CANCELLED,
        ];
    }


}
