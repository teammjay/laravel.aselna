<?php

namespace App\Jobs\Concerns;

use App\Helpers\Common;
use App\Models\Store;

/**
 * Store selection for jobs implementing App\Jobs\Contracts\StoreScopedJob.
 *
 * $storeIds:
 *  - null          => default behaviour: the cron store mapping (store_scheduled_cron_jobs),
 *                     else every eligible store. This is what the scheduler and CLI get.
 *  - ['id', ...]   => only these stores; the cron mapping is ignored.
 *  - [] / ''       => normalised to null, so legacy `new $class([])` calls keep meaning "default".
 */
trait ScopesToStores
{
    public ?array $storeIds = null;

    /**
     * Picked up by the Queue::createPayloadUsing hook in AppServiceProvider and used for the
     * job_statuses tags. Only set when the job is run for chosen stores.
     */
    public $store_name;

    public static function onlyActiveStores(): bool
    {
        return true;
    }

    public static function defaultStoreIds(): ?array
    {
        return null;
    }

    /**
     * The scheduled_cron_jobs class whose store mapping applies to this job.
     * Overridden by jobs that share a sibling's mapping (e.g. the Existing*ListingItemSyncJob jobs).
     */
    public static function storeMappingClass(): string
    {
        return static::class;
    }

    protected function initStoreScope(string|array|null $storeIds): void
    {
        if (is_string($storeIds)) {
            $storeIds = $storeIds === '' ? [] : [$storeIds];
        }

        $storeIds = array_values(array_unique(array_filter($storeIds ?? [])));

        $this->storeIds = empty($storeIds) ? null : $storeIds;

        if (!empty($this->storeIds)) {
            $this->store_name = Store::whereIn('id', $this->storeIds)->pluck('name')->implode(', ');
        }
    }

    /**
     * Store ids to restrict the job's store query to, or null for no restriction.
     */
    protected function resolveStoreIds(): ?array
    {
        if (!empty($this->storeIds)) {
            return $this->storeIds;
        }

        $types = static::eligibleStoreTypes();
        $ids = Common::getActiveStoreJobs(static::storeMappingClass(), count($types) === 1 ? $types[0] : '');

        return empty($ids) ? null : $ids;
    }
}
