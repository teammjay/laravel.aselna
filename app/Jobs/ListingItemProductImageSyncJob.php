<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ListingItemProductImage;
use App\Models\ProductImages;
use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps listing_item_product_images in step with the legacy oydera (easyesale_db)
 * listing_item_product_image table for one store.
 *
 * The run is the same three way reconciliation SyncShopifyItems does, and is meant to run after it
 * in the OyderaMasterSwitch chain:
 *   - rows that are only in the source are created,
 *   - rows that are in both are updated, but only when a value actually differs,
 *   - rows that are only here are deleted, because they were removed upstream.
 *
 * The one thing that cannot be copied straight across is product_image_id. Our product_images are
 * built from fonus (see ProductImagesSyncJob), so every one of them was re-created here with an id
 * of our own - not a single source product_image id exists in our table. The local row is found
 * again on product_images.code (the fonus "<product>-<picture>" code the source carries too),
 * narrowed by the product, so the link always points at the image id we hold today rather than the
 * stale one the source remembers. Products that were themselves renumbered here are resolved first,
 * on products.product_code against the source reference_id1, the way SyncShopifyItems does it.
 *
 * Nothing references listing_item_product_images, so the delete pass has no cascade to defuse. It
 * does mean the source is authoritative for the store: rows produced locally by
 * ListingItemImageCreate / ProcessFeedGenerationJob for one of these listing items are removed as
 * extras. That is intended for the oydera backed Shopify stores - it is what clears the duplicate
 * rows earlier runs left behind - and is the reason the pass is skipped whenever the source read
 * came back empty.
 */
class ListingItemProductImageSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 600000;

    /**
     * The task this job performs, used in the job_statuses tags.
     */
    public const TASK_LISTING_ITEM_IMAGES = 'listing_item_images';

    /**
     * Rows read from the source per chunk.
     */
    private const READ_CHUNK = 1000;

    /**
     * Ids compared against the source per query during the delete pass.
     */
    private const COMPARE_CHUNK = 500;

    /**
     * Local product ids keyed by the source product id, null when the product is not here at all.
     */
    private array $productIds = [];

    /**
     * Picked up by the Queue::createPayloadUsing hook in AppServiceProvider and used for the
     * job_statuses tags, so the dashboard shows which store the job is for.
     */
    public $store_name;

    /**
     * Create a new job instance.
     *
     * @param  string  $storeId  The store to sync the listing item images for.
     */
    public function __construct(
        public string $storeId
    ) {
        $this->store_name = Store::where('id', $storeId)->value('name') ?? $storeId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => $this->store_name . ' ' . self::TASK_LISTING_ITEM_IMAGES
        ]);

        try {
            $stats = $this->listingItemImagesByStore($this->storeId);

            $summary = $this->formatStats($stats);

            Log::info('ListingItemProductImageSyncJob for ' . $this->store_name . ' :: ' . $summary);

            $this->updateTrackingRecord([
                'status' => 'completed',
                'output' => $summary,
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Listing Item Product Images Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error($e->getMessage());
        }
    }

    private function listingItemImagesByStore(string $storeId): array
    {
        $stats = $this->newStats();

        // product_image is joined in for the code and product the local image is found by; it also
        // means a link whose image is gone upstream is never written. The delete pass reads the
        // source table on its own, so a row dropped by this join is not treated as an extra.
        $this->sourceImages($storeId)
            ->join('product_image', 'listing_item_product_image.product_image_id', '=', 'product_image.id')
            ->select('listing_item_product_image.*', 'product_image.product_id', 'product_image.code')
            // Keyset paging, not chunk(): every page re-runs the store subquery against the legacy
            // connection, and an OFFSET that grows with it makes the last pages far slower than the
            // first.
            ->chunkById(self::READ_CHUNK, function ($links) use (&$stats) {
                $links = $links->unique('id');

                $existing = ListingItemProductImage::whereIn('id', $links->pluck('id')->all())->get()->keyBy('id');

                // Images of a listing item that is not here would be rows nothing can reach.
                $listingItemIds = DB::table('listing_item')
                    ->whereIn('id', $links->pluck('listing_item_id')->filter()->unique()->all())
                    ->pluck('id')
                    ->flip();

                $productImages = $this->resolveProductImages($links);

                foreach ($links as $link) {
                    if (!$listingItemIds->has($link->listing_item_id)) {
                        $stats['items_missing']++;
                        Log::warning('ListingItemProductImageSyncJob: listing item ' . $link->listing_item_id
                            . ' is not here, image link ' . $link->id . ' not synced.');

                        continue;
                    }

                    $productImage = $productImages[$link->product_image_id] ?? null;

                    if (!$productImage) {
                        $stats['unresolved']++;
                        Log::warning('ListingItemProductImageSyncJob: no local product image for source image '
                            . $link->product_image_id . ' (code ' . $link->code . ', product ' . $link->product_id
                            . '), link ' . $link->id . ' not synced.');

                        continue;
                    }

                    $this->saveRecord($link->id, $this->mapImageLink($link, $productImage), $stats, $existing);
                }
            }, 'listing_item_product_image.id', 'id');

        if ($this->sourceWasRead($stats)) {
            $this->deleteExtraImageLinks($storeId, $stats);
        } else {
            Log::warning('ListingItemProductImageSyncJob: the source returned no listing_item_product_image rows for '
                . $this->store_name . ', delete pass skipped.');
        }

        return $stats;
    }

    /**
     * The source image links of one store, by way of the store's source listing items.
     */
    private function sourceImages(string $storeId)
    {
        return DB::connection('easyesale_db')->table('listing_item_product_image')
            ->whereIn('listing_item_id', function ($query) use ($storeId) {
                $query->select('listing_item.id')
                    ->from('listing_item')
                    ->join('store_product', 'listing_item.store_product_id', '=', 'store_product.id')
                    ->where('store_product.store_id', $storeId);
            });
    }

    /**
     * Image links of this store that are gone from the source. Links of a listing item that itself
     * disappeared are removed with the item, by SyncShopifyItems::deleteListingItems().
     */
    private function deleteExtraImageLinks(string $storeId, array &$stats): void
    {
        $this->localListingItemIds($storeId)
            ->chunkById(self::COMPARE_CHUNK, function ($rows) use (&$stats) {
                $listingItemIds = $rows->pluck('id')->all();

                $localIds = DB::table('listing_item_product_images')
                    ->whereIn('listing_item_id', $listingItemIds)
                    ->pluck('id')
                    ->all();

                if (empty($localIds)) {
                    return;
                }

                $sourceIds = DB::connection('easyesale_db')->table('listing_item_product_image')
                    ->whereIn('listing_item_id', $listingItemIds)
                    ->distinct()
                    ->pluck('id')
                    ->all();

                $stats['deleted'] += $this->deleteInChunks(array_values(array_diff($localIds, $sourceIds)));
            });
    }

    /**
     * Create the row, update only the values that actually differ, or leave it alone.
     *
     * @param  Collection  $existing  The rows of this chunk that are already here, keyed by id.
     */
    private function saveRecord(string $id, array $attributes, array &$stats, Collection $existing): void
    {
        $model = $existing->get($id);

        if (!$model) {
            $model = new ListingItemProductImage();
            $model->forceFill($attributes + ['id' => $id]);
            $model->save();

            $stats['created']++;

            return;
        }

        $changed = $this->changedAttributes($model, $attributes);

        if (empty($changed)) {
            $stats['unchanged']++;

            return;
        }

        $model->forceFill($changed);
        $model->save();

        $stats['updated']++;
        Log::info('Updated ListingItemProductImage ' . $id . ' (' . implode(', ', array_keys($changed)) . ')');
    }

    /**
     * The local product image of every source image in a chunk, keyed by the source image id.
     *
     * Matched on product_images.code, narrowed by the product so two products that happen to share
     * a code cannot be mixed up, and falling back to the code alone when the product cannot be
     * placed. The newest row wins, because the code is what survives an image being re-imported
     * under a new id.
     *
     * @return array<string, ProductImages>
     */
    private function resolveProductImages($links): array
    {
        $this->resolveProductIds($links->pluck('product_id')->all());

        $codes = $links->pluck('code')->filter()->unique()->all();

        if (empty($codes)) {
            return [];
        }

        $byProductAndCode = [];
        $byCode = [];

        // Oldest first, so the last row written to each key is the latest image holding that code.
        ProductImages::whereIn('code', $codes)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'product_id', 'code', 'main_image', 'image'])
            ->each(function ($productImage) use (&$byProductAndCode, &$byCode) {
                $byProductAndCode[$productImage->product_id . '|' . $productImage->code] = $productImage;
                $byCode[$productImage->code] = $productImage;
            });

        $resolved = [];

        foreach ($links as $link) {
            if (isset($resolved[$link->product_image_id]) || empty($link->code)) {
                continue;
            }

            $productId = $this->productIds[$link->product_id] ?? $link->product_id;

            $productImage = $byProductAndCode[$productId . '|' . $link->code] ?? $byCode[$link->code] ?? null;

            if ($productImage) {
                $resolved[$link->product_image_id] = $productImage;
            }
        }

        return $resolved;
    }

    /**
     * Fill in the local product id for every source product id of a chunk.
     *
     * Most of them are the same row on both sides. The ones that are not were re-created here with
     * an id of our own, and are found again on their code - the source reference_id1 first, the way
     * SyncShopifyItems matches them, then the source product code.
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
            $this->productIds[$sourceProduct->id] = $byCode->get($sourceProduct->reference_id1) ?? $byCode->get($sourceProduct->code);
        }

        // Ids with no row in the source product table at all.
        foreach ($renumbered as $sourceProductId) {
            if (!array_key_exists($sourceProductId, $this->productIds)) {
                $this->productIds[$sourceProductId] = null;
            }
        }
    }

    /**
     * The attributes whose source value differs from what is stored.
     */
    private function changedAttributes(ListingItemProductImage $model, array $attributes): array
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
     * written differently - the legacy connection hands back real booleans where we store 0/1.
     */
    private function sameValue($current, $value): bool
    {
        $current = is_bool($current) ? (int) $current : $current;
        $value = is_bool($value) ? (int) $value : $value;

        if (is_null($current) || is_null($value)) {
            return is_null($current) && is_null($value);
        }

        if (is_numeric($current) && is_numeric($value)) {
            return (float) $current === (float) $value;
        }

        return (string) $current === (string) $value;
    }

    private function deleteInChunks(array $ids): int
    {
        $deleted = 0;

        foreach (array_chunk($ids, self::COMPARE_CHUNK) as $chunk) {
            Log::info('Deleting ' . count($chunk) . ' listing item images removed from the source.');
            $deleted += DB::table('listing_item_product_images')->whereIn('id', $chunk)->delete();
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
            ->select('id');
    }

    private function newStats(): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'unresolved' => 0,
            'items_missing' => 0,
            'deleted' => 0,
        ];
    }

    /**
     * Whether the source read produced anything. Nothing is deleted off an empty read.
     */
    private function sourceWasRead(array $stats): bool
    {
        return ($stats['created'] + $stats['updated'] + $stats['unchanged'] + $stats['unresolved'] + $stats['items_missing']) > 0;
    }

    private function formatStats(array $stats): string
    {
        $parts = [];

        foreach ($stats as $key => $count) {
            $parts[] = $key . ': ' . $count;
        }

        return implode(', ', $parts);
    }

    /**
     * main_image comes from our own product image rather than the source one, because that is the
     * flag the rest of the app reads. image_file_system_id keeps the source value where there is
     * one and otherwise points at the sys_file our product image holds, the way
     * ListingItemImageCreate writes it.
     */
    private function mapImageLink(mixed $link, ProductImages $productImage): array
    {
        return [
            'listing_item_id' => $link->listing_item_id,
            'product_image_id' => $productImage->id,
            'image_file_system_id' => $link->image_file_system_id ?: $productImage->image,
            'main_image' => $productImage->main_image ? 1 : 0,
            'path' => $link->path,
            'reference_id' => $link->reference_id,
        ];
    }
}
