<?php

namespace App\Jobs;

use App\Constants\ListingSyncStatus;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\AmazonAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AmazonListingItemSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $amazon_store_ids = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'amazon');
        })->pluck('id');

        //join listing_item_link and listing_item and listing
        ListingItemLink::query()
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $amazon_store_ids)
            ->whereNull('listing_item.store_sku')
            ->whereIn('listing_item_link.sync_status', [
                ListingSyncStatus::Synchronizing,
            ])
//            ->whereIn('listing.id', function ($subquery) use ($amazon_store_ids) {
//                return $subquery->select('listing_id')->from('listing_feed')->whereIn('store_id', $amazon_store_ids);
//            })
            ->chunk(100, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    $this->syncListingItem($listingItemLink);
                }
            });
    }

    private function syncListingItem(mixed $listingItemLink)
    {
        $store = $listingItemLink->listing->store;
        $itemDetail = $listingItemLink->listingItem;

        try {
            $amazonAuthService = new AmazonAuthService();
            $response = $amazonAuthService->fetchListingInfoBySku($itemDetail->sku, $store->user_id);
            Log::info('Summary ::' . json_encode($response['summaries']));
            if ($response['summaries']) {
                $summary = $response['summaries'][0];
                $itemDetail->update([
                    'store_sku' => $summary['asin'],
                    'sync_status' => ListingSyncStatus::Synchronized,
                ]);
            }

        } catch (\Exception $e) {
            $itemDetail->update([
                'sync_status' => ListingSyncStatus::CreationError,
            ]);
            Log::error('Error syncing Amazon listing item: ' . $e->getMessage(), [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'sku' => $itemDetail->sku,
            ]);
        }
    }
}
