<?php

namespace App\Constants;

/**
 * How "Run Now" starts a job on the Saved Jobs / Cron Jobs screens.
 *
 * STORE_PICKER: the job implements App\Jobs\Contracts\StoreScopedJob, so Run Now opens the store popup.
 * SINGLE_RUN:   the job is started as it is configured, with no store choice.
 */
class JobRunMode
{
    const STORE_PICKER = 1;
    const SINGLE_RUN = 2;

    const names = [
        self::STORE_PICKER => 'Store picker',
        self::SINGLE_RUN => 'Single run',
    ];

    const badgeClasses = [
        self::STORE_PICKER => 'badge-info',
        self::SINGLE_RUN => 'badge-secondary',
    ];

    const buttonClasses = [
        self::STORE_PICKER => 'btn-info',
        self::SINGLE_RUN => 'btn-primary',
    ];

    const icons = [
        self::STORE_PICKER => 'fas fa-store',
        self::SINGLE_RUN => 'fas fa-play',
    ];

    const descriptions = [
        self::STORE_PICKER => 'Run Now lets you choose the stores. With none chosen it runs for the default stores, the same as the scheduled run.',
        self::SINGLE_RUN => 'Run Now starts the job as configured. There is no store choice.',
    ];

    public static function getName($id)
    {
        return self::names[$id] ?? null;
    }

    public static function getBadgeClass($id)
    {
        return self::badgeClasses[$id] ?? 'badge-light';
    }

    public static function getButtonClass($id)
    {
        return self::buttonClasses[$id] ?? 'btn-primary';
    }

    public static function getIcon($id)
    {
        return self::icons[$id] ?? '';
    }

    public static function getDescription($id)
    {
        return self::descriptions[$id] ?? '';
    }
}
