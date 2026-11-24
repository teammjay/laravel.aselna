<?php

namespace App\Jobs\Ebay;

use App\Jobs\Concerns\ScopesToStores;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Contracts\StoreScopedJob;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Models\Store;
use App\Services\Auth\EbayAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Downloads every active listing of an eBay store (Trading API GetMyeBaySelling, ActiveList) and checks it
 * against our catalog:
 *  - the eBay item against listing_item (matched on store_sku = eBay ItemID, else sku = eBay SKU), and
 *  - each eBay variation against listing_item_detail (sku = variation SKU under the matched listing item).
 *
 * Read only: nothing is created or changed. The result of each store is written as a CSV to
 * storage/app/private/ebay_listing_downloads/ and the counts go to the job_statuses output.
 *
 * Meant to run weekly; its scheduler entries are commented out for now (JobScheduler and
 * ScheduledCronJobsSeeder) so it is not shown to the client.
 */
class DownloadAllEbayListingJob implements ShouldQueue, ShouldBeUnique, StoreScopedJob
{
    use Queueable, ScopesToStores, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    // Item matched to listing_item
    const ITEM_FOUND_BY_ITEM_ID = 'found_by_item_id';
    const ITEM_FOUND_BY_SKU = 'found_by_sku';
    const ITEM_MISSING = 'missing';

    // Variation matched to listing_item_detail
    const VARIANT_FOUND = 'found';
    const VARIANT_UNDER_OTHER_ITEM = 'found_under_other_item';
    const VARIANT_MISSING = 'missing';
    const VARIANT_NO_SKU = 'no_sku';

    const CSV_HEADER = [
        'store', 'ebay_item_id', 'ebay_sku', 'title', 'ebay_variation_sku', 'quantity',
        'item_status', 'listing_item_id', 'variant_status', 'listing_item_detail_id',
    ];

    const PAGE_ATTEMPTS = 3;

    public $timeout = 7200;

    public $uniqueFor = 7200;

    public $store_type = 'ebay';

    public static function eligibleStoreTypes(): array
    {
        return ['ebay'];
    }

    public function __construct(string|array|null $storeIds = null)
    {
        $this->initStoreScope($storeIds);
    }

    public function uniqueId(): string
    {
        if (empty($this->storeIds)) {
            return 'all';
        }

        $storeIds = $this->storeIds;
        sort($storeIds);

        return implode(',', $storeIds);
    }

    public function handle(): void
    {
        try {
            $this->updateTrackingRecord([
                'status' => 'processing',
                'started_at' => now(),
                'tags' => 'Download All eBay Listing',
            ]);
            Log::info('DownloadAllEbayListingJob started');

            $storeIds = $this->resolveStoreIds();

            $query = Store::whereIn('store_type_id', function ($subquery) {
                return $subquery->select('id')
                    ->from('store_type')
                    ->where('code', 'ebay');
            })->where('store_status', '<>', 0);

            if (!empty($storeIds)) {
                $query->whereIn('id', $storeIds);
            }

            $stores = $query->get();
            $summary = [];

            foreach ($stores as $store) {
                $this->trackCurrentStore($store, $stores->count());
                $summary[$store->name] = $this->downloadStore($store);
            }

            Log::info('DownloadAllEbayListingJob finished', $summary);

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now(),
                'output' => json_encode($summary),
            ]);
        } catch (\Throwable $exception) {
            \App\Helpers\ErrorLogger::log($exception, __METHOD__);

            Log::error('Error in DownloadAllEbayListingJob: ' . $exception->getMessage());

            $this->updateTrackingRecord([
                'status' => 'failed',
                'finished_at' => now(),
                'output' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Page through the store's active eBay listings, check each page against our tables and write the CSV.
     */
    private function downloadStore(Store $store): array
    {
        $stats = [
            'items' => 0,
            'items_found' => 0,
            'items_missing' => 0,
            'variants' => 0,
            'variants_found' => 0,
            'variants_under_other_item' => 0,
            'variants_missing' => 0,
            'variants_no_sku' => 0,
            'pages' => 0,
            'file' => null,
            'error' => null,
        ];

        $handle = null;

        try {
            $ebayAuthService = new EbayAuthService(
                env('EBAY_CLIENT_ID'),
                env('EBAY_CLIENT_SECRET'),
                env('EBAY_REDIRECT_URI'),
                $store->user_id
            );

            $relativePath = 'ebay_listing_downloads/' . str($store->name)->slug('_') . '_' . now()->format('Y_m_d_His') . '.csv';
            Storage::disk('local')->makeDirectory('ebay_listing_downloads');
            $handle = fopen(Storage::disk('local')->path($relativePath), 'w');
            fputcsv($handle, self::CSV_HEADER);
            $stats['file'] = $relativePath;

            $page = 1;
            $totalPages = 1;

            do {
                // Pages with many variations are large; eBay sometimes resets the connection, so retry a page before giving up
                for ($attempt = 1; $attempt <= self::PAGE_ATTEMPTS; $attempt++) {
                    $response = $ebayAuthService->getMyeBaySellingActiveList($page);
                    if (is_array($response)) {
                        break;
                    }
                    if ($attempt < self::PAGE_ATTEMPTS) {
                        Log::warning("DownloadAllEbayListingJob: no response for store {$store->name}, page {$page}, retrying (attempt {$attempt})");
                        sleep(10 * $attempt);
                    }
                }

                if (!is_array($response) || !in_array($response['Ack'] ?? '', ['Success', 'Warning'], true)) {
                    $message = $this->errorMessage($response);
                    $stats['error'] = "Page {$page}: {$message}";

                    CriticalErrorService::report('Ebay download all listing failed', [
                        'marketplace' => 'eBay',
                        'reason' => $message,
                        'method' => __METHOD__,
                    ], ['store_id' => $store->id, 'page' => $page]);

                    Log::error("DownloadAllEbayListingJob: GetMyeBaySelling failed for store {$store->name}, page {$page}: {$message}");
                    break;
                }

                $activeList = $response['ActiveList'] ?? [];
                $totalPages = (int) ($activeList['PaginationResult']['TotalNumberOfPages'] ?? 0);
                $items = $this->asList($activeList['ItemArray']['Item'] ?? []);

                Log::info("DownloadAllEbayListingJob: store {$store->name}, page {$page}/{$totalPages}, " . count($items) . ' items');

                foreach ($this->checkPage($store, $items, $stats) as $row) {
                    fputcsv($handle, $row);
                }

                $stats['pages'] = $page;
                $page++;
            } while ($page <= $totalPages);

        } catch (\Throwable $e) {
            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Ebay download all listing failed', [
                'marketplace' => 'eBay',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine(),
                'method' => __METHOD__,
            ], ['store_id' => $store->id]);

            $stats['error'] = $e->getMessage();
        } finally {
            if ($handle) {
                fclose($handle);
            }
        }

        Log::info("DownloadAllEbayListingJob: store {$store->name} done", $stats);

        return $stats;
    }

    /**
     * Check one page of eBay items against listing_item / listing_item_detail and return its CSV rows.
     */
    private function checkPage(Store $store, array $items, array &$stats): array
    {
        if (empty($items)) {
            return [];
        }

        $ebayItemIds = [];
        $ebaySkus = [];
        $variantSkus = [];

        foreach ($items as $item) {
            $ebayItemIds[] = $this->text($item['ItemID'] ?? '');
            $ebaySkus[] = $this->text($item['SKU'] ?? '');
            foreach ($this->asList($item['Variations']['Variation'] ?? []) as $variation) {
                $variantSkus[] = $this->text($variation['SKU'] ?? '');
            }
        }

        $ebayItemIds = array_values(array_filter(array_unique($ebayItemIds)));
        $ebaySkus = array_values(array_filter(array_unique($ebaySkus)));
        $variantSkus = array_values(array_filter(array_unique($variantSkus)));

        // Listing items of this store matching the page by eBay ItemID or SKU
        $listingItems = ListingItem::query()
            ->select('id', 'sku', 'store_sku')
            ->whereIn('id', $this->storeListingItemIds($store))
            ->where(function ($query) use ($ebayItemIds, $ebaySkus) {
                $query->whereIn('store_sku', $ebayItemIds)->orWhereIn('sku', $ebaySkus);
            })
            ->get();

        $byItemId = $listingItems->filter(fn ($row) => !empty($row->store_sku))->keyBy('store_sku');
        $bySku = $listingItems->filter(fn ($row) => !empty($row->sku))->keyBy('sku');

        // Variants of this store with one of the page's variation SKUs, keyed "<listing_item_id>|<sku>"
        $variants = collect();
        if (!empty($variantSkus)) {
            $variants = ListingItemDetail::query()
                ->select('id', 'listing_item_id', 'sku')
                ->whereIn('listing_item_id', $this->storeListingItemIds($store))
                ->whereIn('sku', $variantSkus)
                ->get();
        }
        $variantsByItemAndSku = $variants->keyBy(fn ($row) => $row->listing_item_id . '|' . $row->sku);
        $variantsBySku = $variants->keyBy('sku');

        $rows = [];

        foreach ($items as $item) {
            $itemId = $this->text($item['ItemID'] ?? '');
            $sku = $this->text($item['SKU'] ?? '');
            $title = $this->text($item['Title'] ?? '');

            $listingItem = null;
            $itemStatus = self::ITEM_MISSING;
            if ($itemId !== '' && $byItemId->has($itemId)) {
                $listingItem = $byItemId->get($itemId);
                $itemStatus = self::ITEM_FOUND_BY_ITEM_ID;
            } elseif ($sku !== '' && $bySku->has($sku)) {
                $listingItem = $bySku->get($sku);
                $itemStatus = self::ITEM_FOUND_BY_SKU;
            }

            $stats['items']++;
            $stats[$listingItem ? 'items_found' : 'items_missing']++;

            if ($itemStatus === self::ITEM_MISSING) {
                Log::info("DownloadAllEbayListingJob: listing item missing. Store: {$store->name} | ItemID: {$itemId} | SKU: {$sku}");
            }

            $variations = $this->asList($item['Variations']['Variation'] ?? []);

            if (empty($variations)) {
                $rows[] = [
                    $store->name, $itemId, $sku, $title, '', $this->text($item['Quantity'] ?? ''),
                    $itemStatus, $listingItem->id ?? '', '', '',
                ];
                continue;
            }

            foreach ($variations as $variation) {
                $variantSku = $this->text($variation['SKU'] ?? '');
                $variant = null;

                if ($variantSku === '') {
                    $variantStatus = self::VARIANT_NO_SKU;
                } elseif ($listingItem && $variantsByItemAndSku->has($listingItem->id . '|' . $variantSku)) {
                    $variant = $variantsByItemAndSku->get($listingItem->id . '|' . $variantSku);
                    $variantStatus = self::VARIANT_FOUND;
                } elseif ($variantsBySku->has($variantSku)) {
                    // Present for this store, but not under the listing item the eBay item matched (or it matched none)
                    $variant = $variantsBySku->get($variantSku);
                    $variantStatus = self::VARIANT_UNDER_OTHER_ITEM;
                } else {
                    $variantStatus = self::VARIANT_MISSING;
                }

                $stats['variants']++;
                $stats['variants_' . $variantStatus]++;

                $rows[] = [
                    $store->name, $itemId, $sku, $title, $variantSku, $this->text($variation['Quantity'] ?? ''),
                    $itemStatus, $listingItem->id ?? '', $variantStatus, $variant->id ?? '',
                ];
            }
        }

        return $rows;
    }

    /**
     * Subquery: ids of the listing items linked to one of the store's listings.
     */
    private function storeListingItemIds(Store $store): \Closure
    {
        return function ($query) use ($store) {
            $query->select('listing_item_link.item_id')
                ->from('listing_item_link')
                ->join('listing', 'listing.id', '=', 'listing_item_link.listing_id')
                ->where('listing.store_id', $store->id);
        };
    }

    /**
     * XML decoded through json_encode gives a single child as an associative array and many as a list.
     */
    private function asList($value): array
    {
        if (empty($value) || !is_array($value)) {
            return [];
        }

        return array_is_list($value) ? $value : [$value];
    }

    /**
     * Empty XML elements decode to [], so turn any value into a trimmed string.
     */
    private function text($value): string
    {
        return is_scalar($value) ? trim((string) $value) : '';
    }

    private function errorMessage($response): string
    {
        if (!is_array($response)) {
            return 'No response from eBay';
        }

        $errors = $this->asList($response['Errors'] ?? []);

        return collect($errors)->map(fn ($error) => $this->text($error['LongMessage'] ?? $error['ShortMessage'] ?? ''))
            ->filter()
            ->implode('; ') ?: 'Ack: ' . $this->text($response['Ack'] ?? 'unknown');
    }
}
