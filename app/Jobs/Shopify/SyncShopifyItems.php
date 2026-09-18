<?php

namespace App\Jobs\Shopify;

use App\Constants\ListingStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
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
            ->chunk(self::READ_CHUNK, function ($storeProducts) use (&$stats) {
                $existing = $this->existingRows(StoreProduct::class, $storeProducts->pluck('id')->all());

                foreach ($storeProducts as $storeProduct) {
                    // The product has to exist in our catalog before the store product can point at it.
                    // The source row is still there, so it is not an extra row either - just skipped.
                    if (Catalog::where('product_id', $storeProduct->product_id)->doesntExist()) {
                        $stats['skipped']++;
                        continue;
                    }

                    $this->saveRecord(StoreProduct::class, $storeProduct->id, $this->mapStoreProduct($storeProduct), [], $stats, $existing);
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

                foreach ($items as $item) {
                    $this->saveRecord(
                        ListingItem::class,
                        $item->id,
                        $this->mapListingItem($item),
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
                        $existing = $this->existingRows(ListingItemDetail::class, $details->pluck('id')->all());

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
                    ->pluck('id')
                    ->all();

                $stats['deleted'] += $this->deleteInChunks('listing_item_detail', 'id', array_values(array_diff($localIds, $sourceIds)));
            });
    }

    /**
     * Store products that are gone from the source. Their listing data goes with them, so it is
     * removed first - and a store product whose listing items had to be kept is kept as well,
     * rather than leaving those items orphaned.
     */
    private function deleteExtraStoreProducts(string $storeId, array &$stats): void
    {
        $extras = $this->findExtraIds(DB::table('store_product')->where('store_id', $storeId), 'store_product');

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
     */
    private function findExtraIds($localQuery, string $sourceTable): array
    {
        $extras = [];

        $localQuery->select('id')->orderBy('id')->chunk(self::COMPARE_CHUNK, function ($rows) use (&$extras, $sourceTable) {
            $ids = $rows->pluck('id')->all();

            $sourceIds = DB::connection('easyesale_db')->table($sourceTable)
                ->whereIn('id', $ids)
                ->pluck('id')
                ->all();

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
            'skipped' => 0,
            'deleted' => 0,
            'protected' => 0,
        ];
    }

    /**
     * Whether the source read produced anything. Nothing is deleted off an empty read.
     */
    private function sourceWasRead(array $stats): bool
    {
        return ($stats['created'] + $stats['updated'] + $stats['unchanged'] + $stats['skipped']) > 0;
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
            'device_id' => $item->device_id,
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
            'device_id' => $listingItemData->device_id,
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
