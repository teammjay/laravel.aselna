<?php

namespace App\Jobs\Contracts;

/**
 * A job that loops over stores and can be restricted to a chosen set of them.
 *
 * Implementations take the store ids as their FIRST constructor argument
 * (string|array|null, null = default behaviour) and use App\Jobs\Concerns\ScopesToStores.
 * The Saved Jobs / Cron Jobs "Run Now" popup is shown for any job implementing this.
 */
interface StoreScopedJob
{
    /**
     * store_type.code values this job can run for. [] = any store type.
     */
    public static function eligibleStoreTypes(): array;

    /**
     * Whether stores with store_status = 0 are excluded (mirrors the job's own store query).
     */
    public static function onlyActiveStores(): bool;

    /**
     * Store ids the job runs for when no stores are given, or null when that is
     * "the cron store mapping, else every eligible store".
     */
    public static function defaultStoreIds(): ?array;
}
