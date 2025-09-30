<?php

namespace App\Jobs;

use App\Constants\FeedStatus;
use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Models\ListingFeed;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\WalmartAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class WalmartListingItemSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

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
        Log::info('WalmartListingItemSyncJob started');
        $walmart_store_ids = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'walmart');
        })->pluck('id');
        Log::info('Walmart store ids: ' . json_encode($walmart_store_ids));

        ListingItemLink::query()
            ->with(['listing','listingItem'])
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $walmart_store_ids)
            ->whereRaw("COALESCE(listing_item.store_sku, '') = ''")
            ->whereIn('listing_item.status', [1,2])
            ->whereRaw('(listing_item.creation_retry_count < 5 OR listing_item.creation_retry_count IS NULL)')
            ->whereIn('listing_item.sync_status', [
                ListingSyncStatus::CreationError,
                ListingSyncStatus::Synchronizing,
            ])
            ->whereNotExists(function ($subquery) use ($walmart_store_ids) {
                $subquery->selectRaw(1)
                    ->from('listing_feeds')
                    ->whereColumn('listing_feeds.listing_id', 'listing.id')
                    ->whereIn('store_id', $walmart_store_ids)
                    ->where('status', FeedStatus::PROCESSING);
            })
            ->orderBy('listing_item.created_at','asc')
            ->chunk(100, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    Log::info('Syncing Walmart listing item: ' . $listingItemLink->item_id);
                    $this->syncListingItem($listingItemLink);
                }
            });

        /*foreach ($walmart_store_ids as $storeId) {
            $store = Store::find($storeId);

            if (!isset($store->user_id)) continue;
            if (env('APP_ENV') === 'local') continue;

            $listingItemLinks = ListingItemLink::query()
                ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
                ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
                ->whereIn('listing.store_id', [$storeId])
                ->where(function ($query) {
                    return $query->whereNull('listing_item.store_sku')
                        ->orWhere('listing_item.store_sku', '');
                })
                ->where(function ($query) {
                    return $query
                        ->where('listing_item.creation_retry_count', '<', 3)->orWhereNull('listing_item.creation_retry_count');
                })
                ->whereNotIn('listing_item.sync_status', [
                    ListingSyncStatus::CreationError,
                ])
                ->limit(100)
                ->get();

//            Log::info("Listing Item Links Query: " . $listingItemLinks);
//            continue;

            $this->syncListingItem($listingItemLinks, $store);
        }*/
    }

    private function syncListingItem($listingItemLink)
    {
        $store = $listingItemLink->listing->store;
        $itemDetail = $listingItemLink->listingItem;
        Log::info("Store Name: " . $store->name);
        Log::info("Listing Item Sku " . $itemDetail->store_sku);
        Log::info("Listing Item UPC " . $itemDetail->upc);
        if($itemDetail->store_sku != null) return;

        try {
            Log::info("Calling Service");

            $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
            $response = $authService->getItemSearch($itemDetail->upc);

            if (isset($response['items'][0])) {
                $item = $response['items'][0];
                if(isset($item['itemId']) && $item['itemId'] != '') {
                    Log::info("Update Listing Item with ".$item['itemId']);
                    $itemDetail->update([
                        'store_sku' => $item['itemId'],
                        'sync_status' => ListingSyncStatus::Synchronized,
                    ]);
                }
            }else{
                $date = \Carbon\Carbon::parse($itemDetail->created_at);
                if ($date->diffInDays(now()) >= 5) {
                    Log::info("Listing item older than 5 days - ".$itemDetail->upc);
                    $itemDetail->update([
                        'status' =>ListingStatus::Deleted,
                    ]);
                }else{
                    $retry_count = $itemDetail->creation_retry_count ?? 0;
                    $itemDetail->update([
                        'creation_retry_count' => $retry_count + 1,
                    ]);
                }
            }

        } catch (\Exception $e) {
            $itemDetail->update([
                'sync_status' => ListingSyncStatus::CreationError,
            ]);
            Log::error('Error syncing Walmart listing item: ' . $e->getMessage(), [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'upc' => $itemDetail->upc,
            ]);
        }
    }

    /*private function syncListingItem_old($listingItemLinks, $store)
    {
        foreach ($listingItemLinks as $listingItemLink) {
            $feed_id = $listingItemLink->feed_id;

            $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
            $feedInfo = ListingFeed::where('id', $feed_id)->first();

            $response = $authService->getFeedStatus($feedInfo->feed_id, $feedInfo->number_of_items);

            Log::info('Feed Status: ' . json_encode($response));

            if ($response['feedStatus'] != 'PROCESSED') return null;

            $itemDetail = $listingItemLink->listingItem;

            try {
                foreach ($response['itemDetails']['itemIngestionStatus'] as $item) {
                    if ($item['sku'] == $itemDetail->sku) {
                        $item_detail = ListingItem::where('sku', $item['SKU'])->first();
                        switch ($item['ingestionStatus']) {
                            case 'SUCCESS':
                                $status = ListingSyncStatus::Synchronized;
                                break;
                            default:
                                $status = ListingSyncStatus::Synchronizing;
                                break;
                        }

                        $retry_count = $itemDetail->creation_retry_count ?? 0;

                        if ($item_detail && isset($item['itemid']) && $item['itemid'] !='') {
                            $item_detail->update([
                                'store_sku' => $item['ItemID'],
                                'sync_status' => $status,
                                'creation_retry_count' => $retry_count + 1,
                            ]);
                            Log::info("Updated item with SKU: " . $item['SKU'] . " and ItemID: " . $item['ItemID']);
                        } else {
                            Log::info("No item found for SKU: " . $item['SKU']);
                        }

                    }
                }
            }catch (\Exception $e){

                $itemDetail->update([
                    'sync_status' => ListingSyncStatus::CreationError,
                ]);
                Log::error('Error syncing walmart listing item: ' . $e->getMessage(), [
                    'feed_id' => $feed_id,
                    'listing_item_id' => $itemDetail->id,
                    'store_id' => $store->id,
                    'sku' => $itemDetail->sku,
                ]);
            }
        }
    }*/
}
