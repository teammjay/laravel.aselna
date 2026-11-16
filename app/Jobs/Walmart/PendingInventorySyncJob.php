<?php

namespace App\Jobs\Walmart;

use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Jobs\Concerns\ScopesToStores;
use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Contracts\StoreScopedJob;
use App\Models\Listing;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Catch-up for Walmart inventory: sends inventory for items that are live on Walmart (store_sku set,
 * Synchronized) but never had an inventory feed (listing_item.inventory_submitted_at empty).
 * Normally the result feed check sends inventory per item as each one clears Walmart's review;
 * this job picks up anything that slipped through (feed timed out, inventory submit failed, ...).
 */
class PendingInventorySyncJob implements ShouldQueue, StoreScopedJob
{
    use Queueable, ScopesToStores, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    /** Inventory feeds sent per store per run, to stay under Walmart's feed rate limits. */
    const MAX_FEEDS_PER_STORE = 5;

    public static function eligibleStoreTypes(): array
    {
        return ['walmart'];
    }

    public function __construct(string|array|null $storeIds = null)
    {
        $this->initStoreScope($storeIds);
    }

    public function handle(): void
    {
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Walmart Pending Inventory']);

            $storeIds = $this->resolveStoreIds();

            $stores = Store::select('store.*')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'walmart')
                ->where('store.store_status', '<>', 0)
                ->when(!empty($storeIds), fn ($query) => $query->whereIn('store.id', $storeIds))
                ->get();

            $service = new WalmartService();
            $feedsSent = 0;

            foreach ($stores as $store) {
                $this->trackCurrentStore($store, $stores->count());

                $listingIds = ListingItemLink::join('listing_item', 'listing_item.id', '=', 'listing_item_link.item_id')
                    ->join('listing', 'listing.id', '=', 'listing_item_link.listing_id')
                    ->where('listing.store_id', $store->id)
                    ->whereNotNull('listing_item.store_sku')
                    ->where('listing_item.store_sku', '!=', '')
                    ->where('listing_item.sync_status', ListingSyncStatus::Synchronized)
                    ->where(function ($query) {
                        $query->whereNull('listing_item.status')
                            ->orWhere('listing_item.status', '!=', ListingStatus::Deleted);
                    })
                    ->whereNull('listing_item.inventory_submitted_at')
                    ->distinct()
                    ->pluck('listing_item_link.listing_id');

                Log::info('Walmart pending inventory: ' . $listingIds->count() . ' listing(s) for store ' . $store->name);

                $sentForStore = 0;
                foreach ($listingIds as $listingId) {
                    if ($sentForStore >= self::MAX_FEEDS_PER_STORE) {
                        break;
                    }

                    try {
                        $listing = Listing::with('store')->find($listingId);
                        if (!$listing) {
                            continue;
                        }
                        if ($service->submitInventoryForReadyItems($listing)) {
                            $sentForStore++;
                        }
                    } catch (\Throwable $e) {
                        CriticalErrorService::report('Walmart Pending Inventory Sync Failed', [
                            'marketplace' => 'Walmart',
                            'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine(),
                            'method' => __METHOD__
                        ], ['listingId' => $listingId, 'store_name' => $store->name]);
                    }
                }

                $feedsSent += $sentForStore;
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'output' => $feedsSent . ' inventory feed(s) sent',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
        }
    }
}
