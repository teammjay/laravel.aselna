<?php

namespace App\Jobs;

use App\Constants\FeedStatus;
use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\EbayAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EbayListingItemSyncJob implements ShouldQueue
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
        Log::info('EbayListingItemSyncJob started');
        $ebay_store_ids = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'ebay');
        })->pluck('id');
        Log::info('Ebay store ids: ' . json_encode($ebay_store_ids));

        foreach ($ebay_store_ids as $storeId) {
            $store = Store::find($storeId);

            if (!isset($store->user_id)) continue;
            if (env('APP_ENV') === 'local' && $store->name == 'nismobile') continue;

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
                ->orderBy('listing_item.created_at','asc')
                ->limit(100)
                ->get();

//            Log::info("Listing Item Links Query: " . $listingItemLinks);
//            continue;

            $this->syncListingItem($listingItemLinks, $store);
        }

    }

    private function syncListingItem($listingItemLinks, $store)
    {
        $skuArray = [];
        foreach ($listingItemLinks as $listingItemLink) {
            $itemDetail = $listingItemLink->listingItem;
            if ($itemDetail->store_sku != null) continue;
            $skuArray[] = $itemDetail->sku;

            $retry_count = $itemDetail->creation_retry_count ?? 0;

            $itemDetail->update([
                'creation_retry_count' => $retry_count + 1,
            ]);
        }
        Log::info("Listing Item Sku Array " . json_encode($skuArray));

        if (count($skuArray) == 0) {
            Log::info("No items to sync for store: " . $store->name);
            return;
        }


        try {
            Log::info("Calling Service");

            $clientId = env('EBAY_CLIENT_ID');
            $clientSecret = env('EBAY_CLIENT_SECRET');
            $redirectUri = env('EBAY_REDIRECT_URI');

            $ebayAuthService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
            $response = $ebayAuthService->getListingItems($skuArray);

            if (isset($response) && isset($response['Ack']) && $response['Ack'] == 'Success') {
                if (isset($response['ItemArray']) && count($response['ItemArray']['Item']) > 0) {
                    foreach ($response['ItemArray']['Item'] as $item) {
                        if ($item['SKU'] == null || $item['SKU'] == '') {
                            Log::info("Item SKU is null or empty, skipping item.");
                            continue;
                        }
                        $itemDetail = ListingItem::where('sku', $item['SKU'])->first();
                        if ($itemDetail) {
                            if(isset($item['ItemID']) && $item['ItemID'] != '') {
                                $itemDetail->update([
                                    'store_sku' => $item['ItemID'],
                                    'sync_status' => ListingSyncStatus::Synchronized,
                                ]);
                                Log::info("Updated item with SKU: " . $item['SKU'] . " and ItemID: " . $item['ItemID']);
                            }else{

                                $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                if ($date->diffInDays(now()) >= 2) {
                                    Log::info("Listing item older than 2 days - ".$itemDetail->sku);
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
                        } else {
                            Log::info("No item found for SKU: " . $item['SKU']);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error syncing Ebay listing item: ' . $e->getMessage(), [
                'store_id' => $store->id,
            ]);
        }
    }
}
