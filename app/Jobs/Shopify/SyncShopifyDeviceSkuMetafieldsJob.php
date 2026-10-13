<?php

namespace App\Jobs\Shopify;

use App\Jobs\Concerns\ScopesToStores;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Contracts\StoreScopedJob;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\ShopifyDeviceSkuSync;
use App\Models\Store;
use App\Services\Auth\ShopifyAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Pushes the product+device combination SKUs (ListingItemDetail.sku) of every Shopify product to
 * the product metafield global.device_skus as {device_code: sku}, so the theme can send the same SKU
 * the Google / Meta feeds use as their id in the GA view_item event. The theme looks the SKU up with the
 * device collection's global.device_code metafield (ShopifyBrandPageSyncJob::getDeviceMetafields()).
 * device.code is used instead of the device UUID because it is unique and 4-6 characters, which keeps
 * the value about 60% smaller and under Shopify's size limit even for a product fitting every device.
 *
 * Dispatched by ProcessFeedGenerationJob when a feed of a Shopify store finishes (that is when new
 * detail rows are created), and run daily as a safety net. Store scoped: the Run Now popup can limit a
 * run to chosen Shopify stores; with none chosen it uses the cron store mapping, else every active
 * Shopify store. The feed trigger is limited to the stores in
 * config('services.shopify.device_sku_feed_store_ids') (NevaWireless for now), see shouldRunAfterFeed(). Only products
 * whose map changed since the last successful push are sent; see shopify_device_sku_syncs.
 *
 * See docs/superpowers/plans/2026-10-12-shopify-ga-device-sku-plan.md.
 */
class SyncShopifyDeviceSkuMetafieldsJob implements ShouldQueue, ShouldBeUnique, StoreScopedJob
{
    use Queueable, ScopesToStores, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public const METAFIELD_NAMESPACE = 'global';

    public const METAFIELD_KEY = 'device_skus';

    /**
     * metafieldsSet takes at most 25 metafields per call.
     */
    public const PUSH_BATCH = 25;

    /**
     * Largest json metafield value Shopify accepts (128 KB on the API version ShopifyAuthService uses).
     */
    public const MAX_VALUE_BYTES = 128 * 1024;

    /**
     * Shopify listing items read per chunk.
     */
    private const READ_CHUNK = 200;

    /**
     * Below this many available cost points the job waits for the bucket to refill.
     */
    private const MIN_AVAILABLE_COST = 200;

    public $timeout = 3600;

    public $tries = 1;

    public $uniqueFor = 3600;

    public $store_type = 'shopify';

    public static function eligibleStoreTypes(): array
    {
        return ['shopify'];
    }

    /**
     * Whether a finished feed of this store should dispatch the job (ProcessFeedGenerationJob). Only the
     * stores in config services.shopify.device_sku_feed_store_ids (env SHOPIFY_DEVICE_SKU_FEED_STORE_IDS),
     * so a feed of any other site never pushes by accident. Manual runs are not limited by this.
     */
    public static function shouldRunAfterFeed(?string $storeId): bool
    {
        return !empty($storeId) && in_array($storeId, config('services.shopify.device_sku_feed_store_ids', []), true);
    }

    /**
     * @param  string|array|null  $storeIds  The store whose feed just finished, or the stores picked in the
     *                                       Run Now popup; null for the default (cron mapping, else all).
     */
    public function __construct(string|array|null $storeIds = null)
    {
        $this->initStoreScope($storeIds);
    }

    public function uniqueId(): string
    {
        if (empty($this->storeIds)) {
            return 'all';
        }

        $ids = $this->storeIds;
        sort($ids);

        return implode(',', $ids);
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Shopify Device SKU Metafields']);

        try {
            $storeIds = $this->resolveStoreIds();

            $stores = Store::select('store.*')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->where('store.store_status', '<>', 0)
                ->when(!empty($storeIds), fn ($query) => $query->whereIn('store.id', $storeIds))
                ->get();

            if ($stores->isEmpty()) {
                $this->updateTrackingRecord([
                    'status' => 'completed',
                    'output' => 'No active Shopify store, nothing to push',
                    'finished_at' => now()
                ]);
                return;
            }

            $summaries = [];
            foreach ($stores as $store) {
                $this->trackCurrentStore($store, $stores->count());
                $stats = $this->syncStore($store);
                $summary = $store->name . ': ' . $this->formatStats($stats);
                Log::info('SyncShopifyDeviceSkuMetafieldsJob ' . $summary);
                $summaries[] = $summary;
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'output' => implode(' | ', $summaries),
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::handle($e, 'Shopify device SKU metafield sync failed', ['store_ids' => $this->storeIds]);

            Log::error('SyncShopifyDeviceSkuMetafieldsJob: ' . $e->getMessage());
        }
    }

    private function syncStore(Store $store): array
    {
        $stats = ['checked' => 0, 'unchanged' => 0, 'pushed' => 0, 'failed' => 0, 'too_large' => 0];
        $shopify = new ShopifyAuthService($store->secret_key, $store->service_url);

        ListingItem::select('listing_item.id', 'listing_item.store_sku')
            ->join('store_product', 'store_product.id', '=', 'listing_item.store_product_id')
            ->where('store_product.store_id', $store->id)
            ->whereNotNull('listing_item.store_sku')
            ->where('listing_item.store_sku', '<>', '')
            ->chunkById(self::READ_CHUNK, function ($items) use ($store, $shopify, &$stats) {
                $this->syncChunk($store, $shopify, $items, $stats);
            }, 'listing_item.id', 'id');

        return $stats;
    }

    private function syncChunk(Store $store, ShopifyAuthService $shopify, $items, array &$stats): void
    {
        $listingItemIds = $items->pluck('id')->all();

        // Details whose device is not in the device table (SyncShopifyItems keeps unmatched source ids)
        // have no code and are left out; the feed only writes devices from the device table either.
        $maps = self::buildDeviceSkuMaps(
            ListingItemDetail::join('device', 'device.id', '=', 'listing_item_detail.device_id')
                ->whereIn('listing_item_detail.listing_item_id', $listingItemIds)
                ->whereNotNull('listing_item_detail.sku')
                ->where('listing_item_detail.sku', '<>', '')
                ->orderBy('listing_item_detail.id')
                ->toBase()
                ->get(['listing_item_detail.listing_item_id', 'device.code as device_code', 'listing_item_detail.sku'])
        );

        $synced = ShopifyDeviceSkuSync::where('store_id', $store->id)
            ->whereIn('listing_item_id', $listingItemIds)
            ->get()
            ->keyBy('listing_item_id');

        $pending = [];
        foreach ($items as $item) {
            $stats['checked']++;

            $map = $maps[$item->id] ?? [];
            $previous = $synced->get($item->id);

            // Nothing was pushed before and there is nothing to push now
            if (empty($map) && !$previous) {
                $stats['unchanged']++;
                continue;
            }

            $json = self::encodeMap($map);
            $hash = sha1($json);

            if ($previous && $previous->payload_hash === $hash) {
                $stats['unchanged']++;
                continue;
            }

            if (strlen($json) > self::MAX_VALUE_BYTES) {
                $stats['too_large']++;
                CriticalErrorService::report('Shopify device SKU metafield too large', [
                    'marketplace' => 'Shopify',
                    'reason' => 'Listing item ' . $item->id . ' has ' . count($map) . ' devices (' . strlen($json) . ' bytes)',
                    'method' => __METHOD__
                ]);
                continue;
            }

            $pending[] = [
                'listing_item_id' => $item->id,
                'owner_id' => self::productGid($item->store_sku),
                'json' => $json,
                'hash' => $hash,
                'device_count' => count($map),
            ];
        }

        foreach (array_chunk($pending, self::PUSH_BATCH) as $batch) {
            $failed = $this->pushBatch($shopify, $batch);

            foreach ($batch as $index => $entry) {
                if (isset($failed[$index])) {
                    $stats['failed']++;
                    Log::warning('SyncShopifyDeviceSkuMetafieldsJob: push failed for listing item ' . $entry['listing_item_id'] . ' :: ' . $failed[$index]);
                    continue;
                }

                ShopifyDeviceSkuSync::updateOrCreate(
                    ['store_id' => $store->id, 'listing_item_id' => $entry['listing_item_id']],
                    ['payload_hash' => $entry['hash'], 'device_count' => $entry['device_count'], 'synced_at' => now()]
                );
                $stats['pushed']++;
            }
        }
    }

    /**
     * Push one batch and return the failed entries as [batch index => reason].
     */
    private function pushBatch(ShopifyAuthService $shopify, array $batch): array
    {
        $metafields = array_map(fn ($entry) => [
            'ownerId' => $entry['owner_id'],
            'namespace' => self::METAFIELD_NAMESPACE,
            'key' => self::METAFIELD_KEY,
            'type' => 'json',
            'value' => $entry['json'],
        ], $batch);

        $response = $shopify->setMetafields($metafields);

        $failed = self::failedIndexes($response, count($batch));

        $this->waitForThrottle($response);

        return $failed;
    }

    /**
     * The batch entries a metafieldsSet response did not write, as [index => reason]. A missing
     * response or top level errors fail the whole batch; userErrors fail the entry their field
     * path points at (["metafields", "3", "value"]), or the whole batch when it has no index.
     */
    public static function failedIndexes(?array $response, int $batchSize): array
    {
        $all = fn (string $reason) => array_fill(0, $batchSize, $reason);

        if (empty($response)) {
            return $all('No response from Shopify');
        }

        if (!empty($response['errors'])) {
            return $all(json_encode($response['errors']));
        }

        if (!isset($response['data']['metafieldsSet'])) {
            return $all('Unexpected response from Shopify');
        }

        $failed = [];
        foreach ($response['data']['metafieldsSet']['userErrors'] ?? [] as $error) {
            $field = $error['field'] ?? [];
            $index = (isset($field[0], $field[1]) && $field[0] === 'metafields' && is_numeric($field[1])) ? (int)$field[1] : null;

            if ($index === null) {
                return $all($error['message'] ?? 'Unknown error');
            }

            $failed[$index] = $error['message'] ?? 'Unknown error';
        }

        return $failed;
    }

    /**
     * Group detail rows (ordered by detail id) into [listing_item_id => [device_code => sku]].
     *
     * The first row per device wins, the same rule ProcessFeedGenerationJob uses to pick the
     * detail (and so the feed id) of a device: orderBy('id') + $itemDetailsByDevice[$device_id] ??= $detail.
     * device.code is unique per device, so keying on it picks the same row. Change both together.
     */
    public static function buildDeviceSkuMaps(iterable $rows): array
    {
        $maps = [];
        foreach ($rows as $row) {
            if ($row->device_code === null || $row->device_code === '' || $row->sku === null || $row->sku === '') {
                continue;
            }
            $maps[$row->listing_item_id][(string)$row->device_code] ??= $row->sku;
        }

        foreach ($maps as &$map) {
            ksort($map, SORT_NATURAL);
        }

        return $maps;
    }

    /**
     * Always a JSON object (never a list, even for keys 0..n), so the same map always gives the same string.
     */
    public static function encodeMap(array $map): string
    {
        return json_encode((object)$map, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * store_sku holds the full product GID when Aselna created the product, and the bare id when it
     * came from FetchShopifyProductsJob / the oydera sync.
     */
    public static function productGid(string $storeSku): string
    {
        return str_starts_with($storeSku, 'gid://') ? $storeSku : 'gid://shopify/Product/' . $storeSku;
    }

    private function waitForThrottle(?array $response): void
    {
        $throttle = $response['extensions']['cost']['throttleStatus'] ?? null;
        if (!$throttle || !isset($throttle['currentlyAvailable'], $throttle['restoreRate']) || $throttle['restoreRate'] <= 0) {
            return;
        }

        $missing = self::MIN_AVAILABLE_COST - $throttle['currentlyAvailable'];
        if ($missing > 0) {
            sleep((int)ceil($missing / $throttle['restoreRate']));
        }
    }

    private function formatStats(array $stats): string
    {
        return implode(', ', array_map(fn ($key, $value) => $key . ' ' . $value, array_keys($stats), $stats));
    }
}
