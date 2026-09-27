<?php

namespace App\Jobs\Concerns;

use App\Helpers\Common;
use App\Models\Store;
use Illuminate\Support\Str;

/**
 * Store selection for jobs implementing App\Jobs\Contracts\StoreScopedJob.
 *
 * $storeIds:
 *  - null          => default behaviour: the cron store mapping (store_scheduled_cron_jobs),
 *                     else every eligible store. This is what the scheduler and CLI get.
 *  - ['id', ...]   => only these stores; the cron mapping is ignored.
 *  - [] / ''       => normalised to null, so legacy `new $class([])` calls keep meaning "default".
 *
 * It also builds the job_statuses tags shown on the Jobs Queue dashboard:
 *   "<task> | Now: <store> (i/n) | Stores: <stores> | Manual run by <user>"
 * TrackableJob::updateTrackingRecord() routes every tags/status update through withStoreTrackingTags().
 */
trait ScopesToStores
{
    public ?array $storeIds = null;

    /**
     * Picked up by the Queue::createPayloadUsing hook in AppServiceProvider and used for the
     * job_statuses tags. Only set when the job is run for chosen stores.
     */
    public $store_name;

    /**
     * User who started the job from the Saved Jobs / Cron Jobs screen; null for scheduled and CLI runs.
     */
    public ?string $triggeredBy = null;

    /** The job's own tag text (e.g. "Shopify Orders"), kept so later updates can re-render it. */
    protected ?string $trackingTask = null;

    /** "Stores: ..." part, set once the job knows which stores it resolved to. */
    protected ?string $storeScopeTag = null;

    protected ?string $currentStoreLabel = null;

    protected int $currentStorePosition = 0;

    protected ?int $currentStoreTotal = null;

    protected string $currentStorePrefix = 'Now';

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

        if (!empty($ids)) {
            $this->storeScopeTag = 'Stores (cron setting): ' . implode(', ', static::storeLabels($ids));
        }

        return empty($ids) ? null : $ids;
    }

    /**
     * "<store name>-<store type code>" for each id, e.g. OdeMobile-shopify, ordered by name.
     *
     * @return string[]
     */
    public static function storeLabels(array $storeIds): array
    {
        if (empty($storeIds)) {
            return [];
        }

        return Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->whereIn('store.id', $storeIds)
            ->orderBy('store.name')
            ->get(['store.name', 'store_type.code'])
            ->map(fn ($store) => $store->name . '-' . $store->code)
            ->all();
    }

    /**
     * Record the store the job is working on now; shown as "Now: <store> (i/n)" in the tags.
     */
    protected function trackCurrentStore(Store|string $store, ?int $total = null): void
    {
        $storeId = is_string($store) ? $store : $store->id;

        $this->currentStorePosition++;
        $this->currentStoreTotal = $total;
        $this->currentStoreLabel = static::storeLabels([$storeId])[0] ?? ($store->name ?? $storeId);

        $this->updateTrackingRecord(['tags' => '']);
    }

    /**
     * The tags for job_statuses. An empty $task keeps the last task text the job wrote.
     */
    public function buildTrackingTags(?string $task = ''): string
    {
        $task = trim((string) $task);
        if ($task === '') {
            $task = $this->trackingTask ?? Str::headline(class_basename(static::class));
        }
        $this->trackingTask = $task;

        $parts = [$task];

        if (!empty($this->currentStoreLabel)) {
            $position = $this->currentStoreTotal ? " ({$this->currentStorePosition}/{$this->currentStoreTotal})" : '';
            $parts[] = $this->currentStorePrefix . ': ' . $this->currentStoreLabel . $position;
        }

        $parts[] = $this->storeScopeTag ?? $this->defaultStoreScopeTag();

        if (!empty($this->triggeredBy)) {
            $parts[] = 'Manual run by ' . $this->triggeredBy;
        }

        return implode(' | ', $parts);
    }

    /**
     * Called by TrackableJob::updateTrackingRecord(): re-renders the tags on every tags or final-status update.
     */
    public function withStoreTrackingTags(array $updates): array
    {
        $status = $updates['status'] ?? null;

        if ($status === 'completed') {
            $this->currentStoreLabel = null;
        } elseif ($status === 'failed' && !empty($this->currentStoreLabel)) {
            $this->currentStorePrefix = 'Failed at';
        }

        if (array_key_exists('tags', $updates) || in_array($status, ['completed', 'failed'], true)) {
            $updates['tags'] = $this->buildTrackingTags((string) ($updates['tags'] ?? ''));
        }

        return $updates;
    }

    protected function defaultStoreScopeTag(): string
    {
        if (!empty($this->storeIds)) {
            return 'Stores: ' . implode(', ', static::storeLabels($this->storeIds));
        }

        $defaultIds = static::defaultStoreIds();
        if ($defaultIds !== null) {
            return 'Stores: ' . implode(', ', static::storeLabels($defaultIds));
        }

        $types = static::eligibleStoreTypes();

        return 'Stores: all ' . (empty($types) ? '' : implode('/', $types) . ' ') . 'stores';
    }
}
