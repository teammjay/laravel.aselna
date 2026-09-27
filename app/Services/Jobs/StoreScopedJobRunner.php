<?php

namespace App\Services\Jobs;

use App\Helpers\Common;
use App\Jobs\Contracts\StoreScopedJob;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Turns "a job class + a store selection" into job instances for the Saved Jobs and Cron Jobs
 * "Run Now" actions, and lists the stores a job can be run for.
 *
 * Store-scoped jobs take the store ids as their first constructor argument. $params are the
 * job's positional constructor arguments (scheduled_cron_jobs.parameters), spread exactly as the
 * scheduler does in routes/console.php; a store selection replaces the first of them.
 */
class StoreScopedJobRunner
{
    public function isStoreScoped(?string $class): bool
    {
        return !empty($class) && class_exists($class) && is_subclass_of($class, StoreScopedJob::class);
    }

    /**
     * @param  string[]  $classes
     * @return string[]
     */
    public function scopedClasses(array $classes): array
    {
        return array_values(array_unique(array_filter($classes, fn ($class) => $this->isStoreScoped($class))));
    }

    /**
     * Stores a single store-scoped class can run for.
     */
    public function eligibleStoresFor(string $class): Collection
    {
        /** @var StoreScopedJob $class */
        $types = $class::eligibleStoreTypes();

        return Store::select('store.id', 'store.name', 'store_type.name as store_type_name', 'store_type.code as store_type_code')
            ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->when(!empty($types), fn ($query) => $query->whereIn('store_type.code', $types))
            ->when($class::onlyActiveStores(), fn ($query) => $query->where('store.store_status', '<>', 0))
            ->orderBy('store_type.name')
            ->orderBy('store.name')
            ->get();
    }

    /**
     * Union of the eligible stores of every store-scoped class in $classes.
     */
    public function eligibleStores(array $classes): Collection
    {
        return collect($this->scopedClasses($classes))
            ->flatMap(fn ($class) => $this->eligibleStoresFor($class))
            ->unique('id')
            ->sortBy(fn ($store) => $store->store_type_name . '|' . $store->name)
            ->values();
    }

    /**
     * The stores the job runs for when it is started without stores (the scheduler's behaviour).
     *
     * @return string[]
     */
    public function defaultStoreIds(string $class): array
    {
        /** @var StoreScopedJob $class */
        $eligibleIds = $this->eligibleStoresFor($class)->pluck('id')->all();

        $ids = $class::defaultStoreIds();

        if ($ids === null && method_exists($class, 'storeMappingClass')) {
            $types = $class::eligibleStoreTypes();
            $ids = Common::getActiveStoreJobs($class::storeMappingClass(), count($types) === 1 ? $types[0] : '') ?: null;
        }

        if ($ids === null) {
            return $eligibleIds;
        }

        return array_values(array_intersect($eligibleIds, $ids));
    }

    /**
     * Throws a ValidationException unless every id is eligible for at least one class in $classes.
     */
    public function assertEligible(array $classes, array $storeIds): void
    {
        if (empty($this->scopedClasses($classes))) {
            throw ValidationException::withMessages([
                'store_ids' => 'This job cannot be run for specific stores.',
            ]);
        }

        $eligibleIds = $this->eligibleStores($classes)->pluck('id')->all();
        $invalid = array_diff($storeIds, $eligibleIds);

        if (!empty($invalid)) {
            throw ValidationException::withMessages([
                'store_ids' => 'These stores are not eligible for this job: ' . implode(', ', $this->storeNames($invalid)),
            ]);
        }
    }

    /**
     * Build the job instance for $class.
     *
     * - $storeIds null  => new $class(...$params), exactly what the scheduler builds.
     * - $storeIds given => the ids eligible for this class replace the first argument; returns
     *   null when none are, so a chain member of another store type is skipped rather than
     *   run for every store.
     * - non-scoped class => new $class(...$params); $storeIds is ignored.
     */
    public function build(string $class, ?array $storeIds, array $params = []): ?object
    {
        $params = array_values($params);

        if ($storeIds === null || !$this->isStoreScoped($class)) {
            return new $class(...$params);
        }

        $eligibleIds = $this->eligibleStoresFor($class)->pluck('id')->all();
        $ids = array_values(array_intersect($storeIds, $eligibleIds));

        if (empty($ids)) {
            return null;
        }

        return new $class($ids, ...array_slice($params, 1));
    }

    /**
     * @return string[]
     */
    public function storeNames(array $storeIds): array
    {
        return Store::whereIn('id', $storeIds)->orderBy('name')->pluck('name')->all();
    }

    /**
     * Payload for the "Run Now" store popup.
     */
    public function storeOptions(array $classes): array
    {
        $scoped = $this->scopedClasses($classes);

        if (empty($scoped)) {
            return ['scoped' => false, 'jobs' => [], 'stores' => []];
        }

        $stores = $this->eligibleStores($scoped);
        // "<store name>-<store type code>", e.g. OdeMobile-shopify, since store names repeat across marketplaces
        $storeLabels = $stores->mapWithKeys(fn ($store) => [$store->id => $store->name . '-' . $store->store_type_code]);
        $defaultIds = [];
        $jobs = [];

        foreach ($scoped as $class) {
            $ids = $this->defaultStoreIds($class);
            $defaultIds = array_merge($defaultIds, $ids);
            $eligibleCount = $this->eligibleStoresFor($class)->count();

            $jobs[] = [
                'name' => class_basename($class),
                'store_types' => $class::eligibleStoreTypes(),
                'default_stores' => count($ids) === $eligibleCount
                    ? 'all eligible stores (' . $eligibleCount . ')'
                    : collect($ids)->map(fn ($id) => $storeLabels[$id] ?? $id)->implode(', '),
            ];
        }

        return [
            'scoped' => true,
            'jobs' => $jobs,
            'stores' => $stores->map(fn ($store) => [
                'id' => $store->id,
                'name' => $store->name,
                'label' => $storeLabels[$store->id],
                'type' => $store->store_type_name,
                'is_default' => in_array($store->id, $defaultIds, true),
            ])->values()->all(),
        ];
    }
}
