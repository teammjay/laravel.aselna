<?php

namespace App\Constants;

class CatalogStatus
{
    const ACTIVE = 1;
    const INACTIVE = 2;
    const DISABLED = 3;
    const DRAFT = 4;
    const TEMPORARY_DISABLED = 5;

    const names = [
        self::ACTIVE => 'active',
        self::INACTIVE => 'inactive',
        self::DISABLED => 'discontinued',
        self::DRAFT => 'draft',
        self::TEMPORARY_DISABLED => 'temporaryDisabled',
    ];

    public static function getStatusName($statusId)
    {
        return self::names[$statusId];
    }

    public static function getFonusStatus($statusId)
    {
        switch ($statusId) {
            case 1:
                return 'active';
            case 2:
                return 'temporaryDisabled';
            case 3:
                return 'discontinued';
        }
        return 'draft';
    }
}
