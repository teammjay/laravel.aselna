<?php

namespace App\Jobs\Shopify;

use App\Constants\ListingStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\Device;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Keeps the Aselna tables in step with the legacy oydera (easyesale_db) tables for one Shopify
 * store, one table per run.
 *
 * Each run is a three way reconciliation against the source:
 *   - rows that are only in the source are created,
 *   - rows that are in both are updated, but only when a value actually differs,
 *   - rows that are only here are deleted, because they were removed upstream.
 *
 * Products and devices are not always the same row on both sides: some of them were re-created
 * here with an id of our own. Those are matched on their code (products on products.product_code
 * against the source reference_id1, devices on device.code), the way OdemobileListingItemSyncJob
 * does it, so nothing is skipped just because an id changed. What cannot be matched at all is
 * logged, since that is a different problem.
 *
 * The delete pass never touches a listing item that an order line or a helpdesk ticket points at:
 * order_item.listing_item_id and hd_ticket.related_listing_item_id are ON DELETE CASCADE, so
 * removing such an item would silently take order lines (and whole orders) with it. Those items
 * are kept and reported instead. See docs/superpowers/plans/2026-09-17-pre-sync-purge-plan.md.
 */
class SyncShopifyItems implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public const TASK_STORE_PRODUCT = 'store_product';

    public const TASK_LISTING_ITEM = 'listing_item';

    public const TASK_LISTING_ITEM_DETAIL = 'listing_item_detail';

    /**
     * Rows read from the source per chunk.
     */
    private const READ_CHUNK = 1000;

    /**
     * Ids compared against the source per query during the delete pass.
     */
    private const COMPARE_CHUNK = 500;

    public $timeout = 300000;

    /**
     * Local product ids keyed by the source product id, null when the product is not here at all.
     */
    private array $productIds = [];

    /**
     * Local device ids keyed by the source device id, null when the device is not here at all.
     */
    private array $deviceIds = [];

    /**
     * Picked up by the Queue::createPayloadUsing hook in AppServiceProvider and used for the
     * job_statuses tags, so the dashboard shows which store the job is for.
     */
    public $store_name;

    /**
     * Create a new job instance.
     *
     * @param  string  $storeId    The store to sync.
     * @param  string  $taskType   One of the TASK_* constants.
     */
    public function __construct(
        public string $storeId,
        public string $taskType
    ) {
        $this->store_name = Store::where('id', $storeId)->value('name') ?? $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => $this->store_name . ' ' . $this->taskType]);

        try {
            $stats = match ($this->taskType) {
                self::TASK_STORE_PRODUCT => $this->syncStoreProducts($this->storeId),
                self::TASK_LISTING_ITEM => $this->syncListingItems($this->storeId),
                self::TASK_LISTING_ITEM_DETAIL => $this->syncListingItemDetails($this->storeId),
                default => throw new InvalidArgumentException('Unknown task type: ' . $this->taskType),
            };

            $summary = $this->formatStats($stats);

            Log::info('SyncShopifyItems ' . $this->taskType . ' for ' . $this->store_name . ' :: ' . $summary);

            $this->updateTrackingRecord([
                'status' => 'completed',
                'output' => $summary,
                'finished_at' => now()
            ]);
        } catch (Exception $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Store Products Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error($e->getMessage());
        }

    }

    private function syncStoreProducts($storeId): array
    {
        $stats = $this->newStats();

        DB::connection('easyesale_db')->table('store_product')
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->chunk(self::READ_CHUNK, function ($storeProducts) use (&$stats, $storeId) {
                $existing = $this->existingRows(StoreProduct::class, $storeProducts->pluck('id')->all());

                $this->resolveProductIds($storeProducts->pluck('product_id')->all());

                $claimed = $this->rowsHeldUnderAnotherId($storeId, $storeProducts, $existing);

                foreach ($storeProducts as $storeProduct) {
                    $productId = $this->productIds[$storeProduct->product_id] ?? null;

                    // store_product.product_id is a foreign key, so a product we cannot place at
                    // all is the one thing that cannot be written. It is reported, not skipped
                    // quietly.
                    if (empty($productId)) {
                        $stats['unresolved']++;
                        Log::error('SyncShopifyItems: no local product for source product ' . $storeProduct->product_id
                            . ' (store product ' . $storeProduct->id . ', store ' . $this->store_name . '), row not synced.');

                        continue;
                    }

                    // The pair is unique, so a row already sitting on it under an id of our own
                    // has to give way before the source row can be written.
                    if (!$existing->has($storeProduct->id) && $claimed->has($productId)) {
                        if (!$this->releaseHeldRow($claimed->get($productId), $stats)) {
                            continue;
                        }

                        $claimed->forget($productId);
                    }

                    $attributes = $this->mapStoreProduct($storeProduct);
                    $attributes['product_id'] = $productId;

                    $this->saveRecord(StoreProduct::class, $storeProduct->id, $attributes, [], $stats, $existing);
                }
            });

        if ($this->sourceWasRead($stats)) {
            $this->deleteExtraStoreProducts($storeId, $stats);
        } else {
            Log::warning('SyncShopifyItems: the source returned no store_product rows for ' . $this->store_name . ', delete pass skipped.');
        }

        return $stats;
    }

    private function syncListingItems($storeId): array
    {
        $stats = $this->newStats();

        DB::connection('easyesale_db')->table('listing_item')
            ->whereIn('store_product_id', function ($query) use ($storeId) {
                $query->select('store_product.id')
                    ->from('store_product')
                    ->where('store_id', $storeId);
            })
            ->orderBy('id')
            ->chunk(self::READ_CHUNK, function ($items) use (&$stats) {
                $existing = $this->existingRows(ListingItem::class, $items->pluck('id')->all());

                $this->resolveDeviceIds($items->pluck('device_id')->all(), $stats);

                // The store product already carries the local product id, resolved by code where
                // it had to be, so the item can be pointed at the same product.
                $productIds = StoreProduct::whereIn('id', $items->pluck('store_product_id')->filter()->unique()->all())
                    ->pluck('product_id', 'id');

                foreach ($items as $item) {
                    $attributes = $this->mapListingItem($item);

                    if (!empty($productIds->get($item->store_product_id))) {
                        $attributes['product_id'] = $productIds->get($item->store_product_id);
                    }

                    $this->saveRecord(
                        ListingItem::class,
                        $item->id,
                        $attributes,
                        ['created_at' => $item->create_ts],
                        $stats,
                        $existing
                    );
                }
            });

        if ($this->sourceWasRead($stats)) {
            $this->deleteExtraListingItems($storeId, $stats);
        } else {
            Log::warning('SyncShopifyItems: the source returned no listing_item rows for ' . $this->store_name . ', delete pass skipped.');
        }

        return $stats;
    }

    /**
     * Details are handled a batch of listing items at a time, so the source rows for a whole batch
     * are read in one query instead of one query per listing item.
     */
    private function syncListingItemDetails($storeId): array
    {
        $stats = $this->newStats();

        $this->localListingItemIds($storeId)
            ->chunk(self::COMPARE_CHUNK, function ($rows) use (&$stats) {
                $listingItemIds = $rows->pluck('id')->all();

                Log::info('Syncing details for ' . count($listingItemIds) . ' listing items.');

                DB::connection('easyesale_db')->table('cea_listing_item_detail')
                    ->whereIn('listing_item_id', $listingItemIds)
                    ->orderBy('id')
                    ->chunk(self::READ_CHUNK, function ($details) use (&$stats) {
                        // The legacy table holds every detail row twice, with the same id and the
                        // same values, and ordering by id puts the copies next to each other.
                        $details = $details->unique('id');

                        $existing = $this->existingRows(ListingItemDetail::class, $details->pluck('id')->all());

                        $this->resolveDeviceIds($details->pluck('device_id')->all(), $stats);

                        foreach ($details as $detail) {
                            $this->saveRecord(ListingItemDetail::class, $detail->id, $this->mapListingItemDetail($detail), [], $stats, $existing);
                        }
                    });
            });

        if ($this->sourceWasRead($stats)) {
            $this->deleteExtraListingItemDetails($storeId, $stats);
        } else {
            Log::warning('SyncShopifyItems: the source returned no listing item details for ' . $this->store_name . ', delete pass skipped.');
        }

        return $stats;
    }

    /**
     * Details of this store's listing items that are gone from the source. Details of a listing
     * item that itself disappeared are removed with the item, in deleteListingItems().
     */
    private function deleteExtraListingItemDetails(string $storeId, array &$stats): void
    {
        $this->localListingItemIds($storeId)
            ->chunk(self::COMPARE_CHUNK, function ($rows) use (&$stats) {
                $listingItemIds = $rows->pluck('id')->all();

                $localIds = DB::table('listing_item_detail')->whereIn('listing_item_id', $listingItemIds)->pluck('id')->all();

                if (empty($localIds)) {
                    return;
                }

                $sourceIds = DB::connection('easyesale_db')->table('cea_listing_item_detail')
                    ->whereIn('listing_item_id', $listingItemIds)
                    ->distinct()
                    ->pluck('id')
                    ->all();

                $stats['deleted'] += $this->deleteInChunks('listing_item_detail', 'id', array_values(array_diff($localIds, $sourceIds)));
            });
    }

    /**
     * The rows of this store that already hold a source row's (product_id, store_id) pair under an
     * id of our own, keyed by the local product id.
     *
     * store_product has a unique index on that pair, so the source row cannot simply be inserted
     * on top of one: without this the insert dies with a 1062 and takes the whole run with it.
     * Only source rows that are not here by id can run into this.
     *
     * @param  Collection  $existing  The rows of this chunk that are already here, keyed by id.
     */
    private function rowsHeldUnderAnotherId(string $storeId, $storeProducts, Collection $existing): Collection
    {
        $productIds = [];

        foreach ($storeProducts as $storeProduct) {
            if ($existing->has($storeProduct->id)) {
                continue;
            }

            $productId = $this->productIds[$storeProduct->product_id] ?? null;

            if (!empty($productId)) {
                $productIds[] = $productId;
            }
        }

        if (empty($productIds)) {
            return new Collection();
        }

        return StoreProduct::where('store_id', $storeId)
            ->whereIn('product_id', array_values(array_unique($productIds)))
            ->whereNotIn('id', $storeProducts->pluck('id')->all())
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Free the (product_id, store_id) pair a local row is sitting on so the source row can take
     * its place. The row is one the delete pass would remove anyway - it is not in the source
     * under this id - so it goes the same way, and under the same rule: a row whose listing items
     * an order or a ticket points at is kept and reported instead, and the source row waits.
     *
     * @return bool  Whether the pair is now free.
     */
    private function releaseHeldRow(Model $held, array &$stats): bool
    {
        $listingItemIds = DB::table('listing_item')->where('store_product_id', $held->id)->pluck('id')->all();

        if (!empty($listingItemIds)) {
            $this->deleteListingItems($listingItemIds, $stats);
        }

        if (DB::table('listing_item')->where('store_product_id', $held->id)->exists()) {
            $stats['conflicts']++;
            Log::warning('SyncShopifyItems: store product ' . $held->id . ' holds product ' . $held->product_id
                . ' for ' . $this->store_name . ' and its listing items are referenced by orders or tickets,'
                . ' so the source row for that product was not written.');

            return false;
        }

        DB::table('store_product')->where('id', $held->id)->delete();
        $stats['deleted']++;

        Log::info('SyncShopifyItems: replaced local store product ' . $held->id . ' with the source row for product ' . $held->product_id . '.');

        return true;
    }

    /**
     * Store products that are gone from the source. Their listing data goes with them, so it is
     * removed first - and a store product whose listing items had to be kept is kept as well,
     * rather than leaving those items orphaned.
     */
    private function deleteExtraStoreProducts(string $storeId, array &$stats): void
    {
        // Scoped to the same store on both sides: a store product the source moved to another
        // store is gone from this one and has to go here too, or the two counts never meet.
        $extras = $this->findExtraIds(
            DB::table('store_product')->where('store_id', $storeId),
            'store_product',
            fn ($query) => $query->where('store_id', $storeId)
        );

        if (empty($extras)) {
            return;
        }

        Log::info('Store products removed from the source for ' . $this->store_name . ': ' . count($extras));

        foreach (array_chunk($extras, self::COMPARE_CHUNK) as $chunk) {
            $listingItemIds = DB::table('listing_item')->whereIn('store_product_id', $chunk)->pluck('id')->all();

            if (!empty($listingItemIds)) {
                $this->deleteListingItems($listingItemIds, $stats);
            }

            // Anything still holding listing items is protected, so leave the parent row alone too.
            $blocked = DB::table('listing_item')
                ->whereIn('store_product_id', $chunk)
                ->distinct()
                ->pluck('store_product_id')
                ->all();

            if (!empty($blocked)) {
                Log::warning('Keeping ' . count($blocked) . ' store products: their listing items are referenced by orders or tickets.');
            }

            $deletable = array_values(array_diff($chunk, $blocked));

            if (!empty($deletable)) {
                $stats['deleted'] += DB::table('store_product')->whereIn('id', $deletable)->delete();
            }
        }
    }

    /**
     * Listing items of this store that are gone from the source.
     */
    private function deleteExtraListingItems(string $storeId, array &$stats): void
    {
        DB::table('store_product')
            ->where('store_id', $storeId)
            ->orderBy('id')
            ->select('id')
            ->chunk(self::COMPARE_CHUNK, function ($storeProducts) use (&$stats) {
                $storeProductIds = $storeProducts->pluck('id')->all();

                $localIds = DB::table('listing_item')->whereIn('store_product_id', $storeProductIds)->pluck('id')->all();

                if (empty($localIds)) {
                    return;
                }

                $sourceIds = DB::connection('easyesale_db')->table('listing_item')
                    ->whereIn('store_product_id', $storeProductIds)
                    ->pluck('id')
                    ->all();

                $extras = array_values(array_diff($localIds, $sourceIds));

                if (!empty($extras)) {
                    $this->deleteListingItems($extras, $stats);
                }
            });
    }

    /**
     * Delete listing items and everything hanging off them, except the ones an order line or a
     * helpdesk ticket points at - those cascade into the order tables and are kept instead.
     */
    private function deleteListingItems(array $listingItemIds, array &$stats): void
    {
        foreach (array_chunk($listingItemIds, self::COMPARE_CHUNK) as $chunk) {
            $protected = array_unique(array_merge(
                DB::table('order_item')->whereIn('listing_item_id', $chunk)->distinct()->pluck('listing_item_id')->all(),
                DB::table('hd_ticket')->whereIn('related_listing_item_id', $chunk)->distinct()->pluck('related_listing_item_id')->all()
            ));

            if (!empty($protected)) {
                $stats['protected'] += count($protected);
                Log::warning('Keeping ' . count($protected) . ' listing items that are referenced by orders or tickets: ' . implode(', ', $protected));
            }

            $deletable = array_values(array_diff($chunk, $protected));

            if (empty($deletable)) {
                continue;
            }

            Log::info('Deleting ' . count($deletable) . ' listing items removed from the source.');

            // Children first: none of these have a foreign key back to listing_item, so nothing
            // cleans them up on its own.
            DB::table('listing_item_product_images')->whereIn('listing_item_id', $deletable)->delete();
            DB::table('listing_item_detail')->whereIn('listing_item_id', $deletable)->delete();
            DB::table('listing_item_link')->whereIn('item_id', $deletable)->delete();
            DB::table('listing_item_history')->whereIn('item_id', $deletable)->delete();

            $stats['deleted'] += DB::table('listing_item')->whereIn('id', $deletable)->delete();
        }
    }

    /**
     * Create the row, update only the values that actually differ, or leave it alone.
     *
     * @param  array  $attributes  Values kept in step with the source on every run.
     * @param  array  $createOnly  Values written once, when the row is created.
     * @param  Collection|null  $existing  The rows of this chunk that are already here, keyed by id.
     */
    private function saveRecord(string $modelClass, string $id, array $attributes, array $createOnly, array &$stats, ?Collection $existing = null): void
    {
        /** @var Model|null $model */
        $model = $existing !== null ? $existing->get($id) : $modelClass::find($id);

        if (!$model) {
            $model = new $modelClass();
            $model->forceFill($attributes + $createOnly + [
                'id' => $id,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]);
            $model->save();

            $stats['created']++;
            Log::info('Created ' . class_basename($modelClass) . ' ' . $id);

            return;
        }

        $changed = $this->changedAttributes($model, $attributes);

        if (empty($changed)) {
            $stats['unchanged']++;

            return;
        }

        $model->forceFill($changed);
        $model->updated_by = 'system';
        $model->save();

        $stats['updated']++;
        Log::info('Updated ' . class_basename($modelClass) . ' ' . $id . ' (' . implode(', ', array_keys($changed)) . ')');
    }

    /**
     * Fill in the local product id for every source product id of a chunk.
     *
     * Most of them are the same row on both sides. The ones that are not were re-created here with
     * an id of our own, and are found again on their code - the source reference_id1 first, the
     * way OdemobileListingItemSyncJob matches them, then the source product code.
     */
    private function resolveProductIds(array $sourceProductIds): void
    {
        $unknown = array_values(array_diff(array_filter(array_unique($sourceProductIds)), array_keys($this->productIds)));

        if (empty($unknown)) {
            return;
        }

        foreach (Catalog::whereIn('product_id', $unknown)->pluck('product_id') as $productId) {
            $this->productIds[$productId] = $productId;
        }

        $renumbered = array_values(array_diff($unknown, array_keys($this->productIds)));

        if (empty($renumbered)) {
            return;
        }

        $sourceProducts = DB::connection('easyesale_db')->table('product')
            ->whereIn('id', $renumbered)
            ->select('id', 'code', 'reference_id1')
            ->get();

        $codes = $sourceProducts->pluck('reference_id1')->merge($sourceProducts->pluck('code'))->filter()->unique()->all();

        $byCode = empty($codes) ? collect() : Catalog::whereIn('product_code', $codes)->pluck('product_id', 'product_code');

        foreach ($sourceProducts as $sourceProduct) {
            $productId = $byCode->get($sourceProduct->reference_id1) ?? $byCode->get($sourceProduct->code);

            $this->productIds[$sourceProduct->id] = $productId;

            if (!empty($productId)) {
                Log::info('Matched source product ' . $sourceProduct->id . ' to local product ' . $productId
                    . ' on code ' . ($sourceProduct->reference_id1 ?? $sourceProduct->code) . '.');
            }
        }

        // Ids with no row in the source product table at all.
        foreach ($renumbered as $sourceProductId) {
            if (!array_key_exists($sourceProductId, $this->productIds)) {
                $this->productIds[$sourceProductId] = null;
            }
        }
    }

    /**
     * Fill in the local device id for every source device id of a chunk, the same way as products
     * but on device.code.
     */
    private function resolveDeviceIds(array $sourceDeviceIds, array &$stats): void
    {
        $unknown = array_values(array_diff(array_filter(array_unique($sourceDeviceIds)), array_keys($this->deviceIds)));

        if (empty($unknown)) {
            return;
        }

        foreach (Device::whereIn('id', $unknown)->pluck('id') as $deviceId) {
            $this->deviceIds[$deviceId] = $deviceId;
        }

        $renumbered = array_values(array_diff($unknown, array_keys($this->deviceIds)));

        if (empty($renumbered)) {
            return;
        }

        $sourceDevices = DB::connection('easyesale_db')->table('cea_device')
            ->whereIn('id', $renumbered)
            ->select('id', 'code')
            ->get();

        $codes = $sourceDevices->pluck('code')->filter()->unique()->all();

        $byCode = empty($codes) ? collect() : Device::whereIn('code', $codes)->pluck('id', 'code');

        foreach ($sourceDevices as $sourceDevice) {
            $deviceId = $byCode->get($sourceDevice->code);

            $this->deviceIds[$sourceDevice->id] = $deviceId;

            if (!empty($deviceId)) {
                Log::info('Matched source device ' . $sourceDevice->id . ' to local device ' . $deviceId
                    . ' on code ' . $sourceDevice->code . '.');
            }
        }

        foreach ($renumbered as $sourceDeviceId) {
            if (empty($this->deviceIds[$sourceDeviceId])) {
                $this->deviceIds[$sourceDeviceId] = null;
                $stats['devices_unmatched']++;
                Log::warning('SyncShopifyItems: no local device for source device ' . $sourceDeviceId
                    . ', the rows using it keep the source id.');
            }
        }
    }

    /**
     * The local id of a source device. Unknown devices keep the source id rather than losing it,
     * and were already reported by resolveDeviceIds().
     */
    private function localDeviceId($sourceDeviceId)
    {
        if (empty($sourceDeviceId)) {
            return $sourceDeviceId;
        }

        return $this->deviceIds[$sourceDeviceId] ?? $sourceDeviceId;
    }

    /**
     * The rows of a source chunk that are already here, keyed by id, so the compare pass costs one
     * query per chunk instead of one per row.
     */
    private function existingRows(string $modelClass, array $ids): Collection
    {
        return $modelClass::whereIn('id', $ids)->get()->keyBy('id');
    }

    /**
     * The attributes whose source value differs from what is stored.
     */
    private function changedAttributes(Model $model, array $attributes): array
    {
        $changed = [];

        foreach ($attributes as $key => $value) {
            if (!$this->sameValue($model->getRawOriginal($key), $value)) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    /**
     * Compare a stored value with a source value without reporting a change for the same value
     * written differently. The legacy connection hands back real booleans where we store 0/1, and
     * its datetime columns keep microseconds that MySQL rounds away in ours.
     */
    private function sameValue($current, $value): bool
    {
        $current = $this->normalizeValue($current);
        $value = $this->normalizeValue($value);

        if (is_null($current) || is_null($value)) {
            return is_null($current) && is_null($value);
        }

        if (is_numeric($current) && is_numeric($value)) {
            return (float) $current === (float) $value;
        }

        $currentTime = $this->asTimestamp($current);
        $valueTime = $this->asTimestamp($value);

        if ($currentTime !== null && $valueTime !== null) {
            // A date column here against a timestamp there: only the day survived the write.
            if ($this->isDateOnly($current) || $this->isDateOnly($value)) {
                return date('Y-m-d', $currentTime) === date('Y-m-d', $valueTime);
            }

            // A second of slack, because 12:20:41.774201 is stored as 12:20:42.
            return abs($currentTime - $valueTime) <= 1;
        }

        return (string) $current === (string) $value;
    }

    private function normalizeValue($value)
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
     * The unix timestamp of a date or date time string, or null when the value is not one.
     */
    private function asTimestamp($value): ?int
    {
        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}:\d{2})?/', $value)) {
            return null;
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : $timestamp;
    }

    private function isDateOnly($value): bool
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }

    /**
     * Local ids that the source no longer has. Compared in chunks so neither id list is ever
     * held in full.
     *
     * @param  callable|null  $sourceScope  Narrows the source side to the same rows the local
     *                                      query covers, so a row the source moved elsewhere
     *                                      counts as gone rather than as still present.
     */
    private function findExtraIds($localQuery, string $sourceTable, ?callable $sourceScope = null): array
    {
        $extras = [];

        $localQuery->select('id')->orderBy('id')->chunk(self::COMPARE_CHUNK, function ($rows) use (&$extras, $sourceTable, $sourceScope) {
            $ids = $rows->pluck('id')->all();

            $sourceQuery = DB::connection('easyesale_db')->table($sourceTable)->whereIn('id', $ids);

            if ($sourceScope !== null) {
                $sourceScope($sourceQuery);
            }

            $sourceIds = $sourceQuery->pluck('id')->all();

            $extras = array_merge($extras, array_values(array_diff($ids, $sourceIds)));
        });

        return $extras;
    }

    private function deleteInChunks(string $table, string $column, array $ids): int
    {
        $deleted = 0;

        foreach (array_chunk($ids, self::COMPARE_CHUNK) as $chunk) {
            Log::info('Deleting ' . count($chunk) . ' rows from ' . $table . ' removed from the source.');
            $deleted += DB::table($table)->whereIn($column, $chunk)->delete();
        }

        return $deleted;
    }

    private function localListingItemIds(string $storeId)
    {
        return DB::table('listing_item')
            ->whereIn('store_product_id', function ($query) use ($storeId) {
                $query->select('store_product.id')
                    ->from('store_product')
                    ->where('store_id', $storeId);
            })
            ->select('id')
            ->orderBy('id');
    }

    private function newStats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'unresolved' => 0,
            'devices_unmatched' => 0,
            'deleted' => 0,
            'protected' => 0,
            'conflicts' => 0,
        ];
    }

    /**
     * Whether the source read produced anything. Nothing is deleted off an empty read.
     */
    private function sourceWasRead(array $stats): bool
    {
        return ($stats['created'] + $stats['updated'] + $stats['unchanged'] + $stats['unresolved'] + $stats['conflicts']) > 0;
    }

    private function formatStats(array $stats): string
    {
        $parts = [];

        foreach ($stats as $key => $count) {
            $parts[] = $key . ': ' . $count;
        }

        return implode(', ', $parts);
    }

    private function mapStoreProduct(mixed $storeProduct): array
    {
        return [
            'product_id' => $storeProduct->product_id,
            'store_id' => $storeProduct->store_id,
            'active' => $storeProduct->active ? 1 : 0,
            'brand_combo' => $storeProduct->brand_combo,
            'carrier_combo' => $storeProduct->carrier_combo,
            'store_type_category_id' => $storeProduct->channel_category_id,
            'client' => $storeProduct->client,
            'compare_at_price' => $storeProduct->compare_at_price,
            'description' => $storeProduct->description,
            'errors' => $storeProduct->errors,
            'fulfillment_by_channel_price' => $storeProduct->fulfillment_by_channel_price,
            'fulfillment_type' => $storeProduct->fulfillment_type,
            'include_carriers' => $storeProduct->include_carriers,
            'last_sale_date' => $storeProduct->last_sale_date,
            'last_synch_date_time' => $storeProduct->last_synch_date_time,
            'last_update_date_time' => $storeProduct->last_update_date_time,
            'listing_generation_type_id' => $storeProduct->listing_generation_type_id,
            'listing_title_template_id' => $storeProduct->listing_title_template_id,
            'manufacturer_id' => $storeProduct->manufacturer_id,
            'msrp' => $storeProduct->msrp,
            'overwrite_compare_at_price' => $storeProduct->overwrite_compare_at_price,
            'overwrite_description' => $storeProduct->overwrite_description,
            'overwrite_fulfillment_by_channel_price' => $storeProduct->overwrite_fulfillment_by_channel_price,
            'overwrite_msrp' => $storeProduct->overwrite_msrp,
            'overwrite_price' => $storeProduct->overwrite_price,
            'overwrite_title' => $storeProduct->overwrite_title,
            'overwrite_wholesale_price' => $storeProduct->overwrite_wholesale_price,
            'preserve_title_description' => $storeProduct->preserve_title_description,
            'price' => $storeProduct->price,
            'profit_rate' => $storeProduct->profit_rate,
            'sales_count' => $storeProduct->sales_count,
            'short_tail_keywords' => $storeProduct->short_tail_keywords,
            'synch_inventory' => $storeProduct->synch_inventory,
            'synch_item' => $storeProduct->synch_item,
            'synch_price' => $storeProduct->synch_price,
            'title' => $storeProduct->title,
        ];
    }

    private function mapListingItem(mixed $item): array
    {
        return [
            'title' => $item->title,
            'description' => $item->description,
            'sku' => $item->sku,
            'store_sku' => $item->store_sku,
            'price' => $item->price,
            'sync_status' => $item->sync_status,
            'quantity' => $item->quantity,
            'store_product_id' => $item->store_product_id,
            'status' => $this->getStatus($item->status),
            'store_url' => $item->store_url,
            'parent_id' => $item->parent_id,
            'processing_days' => $item->processing_days,
            'creation_order' => $item->creation_order,
            'creation_retry_count' => $item->creation_retry_count,
            'linked_item_id' => $item->linked_item_id,
            'carrier_id' => $item->carrier_id,
            'device_id' => $this->localDeviceId($item->device_id),
            'generation_type_id' => $item->cea_generation_type_id,
            'brand_id' => $item->cea_brand_id,
            'device_carrier_id' => $item->device_carrier_id,
            'color_id' => $item->color_id,
            'size_id' => $item->size_id,
            'client' => $item->client,
            'upc' => $item->upc,
            'brand_combo' => $item->brand_combo,
            'carrier_combo' => $item->carrier_combo,
            'submit_count' => $item->submit_count,
            'replaced_id' => $item->replaced_id,
            'sync_message' => $item->sync_message,
            'mpn' => $item->mpn,
            'manufacturer_id' => $item->manufacturer_id,
            'sales_count' => $item->sales_count,
            'last_sale_date' => $item->last_sale_date,
            'image_version' => $item->image_version,
            'fulfillment_type' => $item->fulfillment_type,
            'overwrite_price' => $item->overwrite_price,
            'overwrite_msrp' => $item->overwrite_msrp,
            'overwrite_compare_at_price' => $item->overwrite_compare_at_price,
            'overwrite_title' => $item->overwrite_title,
            'overwrite_description' => $item->overwrite_description,
        ];
    }

    private function mapListingItemDetail(mixed $listingItemData): array
    {
        return [
            'sku' => $listingItemData->sku,
            'title' => $listingItemData->title,
            'upc' => $listingItemData->upc,
            'url' => $listingItemData->url,
            'store_sku' => $listingItemData->store_sku,
            'kicked' => $listingItemData->kicked,
            'replaced_id' => $listingItemData->replaced_id,
            'device_id' => $this->localDeviceId($listingItemData->device_id),
            'device_carrier_id' => $listingItemData->device_carrier_id,
            'listing_item_id' => $listingItemData->listing_item_id,
            'last_sale_date' => $listingItemData->last_sale_date,
        ];
    }

    private function getStatus($status)
    {
        switch ($status) {
            case 0:
                return ListingStatus::Active;
            case 1:
                return ListingStatus::Suspended;
            case 2:
                return ListingStatus::Deleted;
            default:
                return ListingStatus::Deleted;
        }
    }
}
