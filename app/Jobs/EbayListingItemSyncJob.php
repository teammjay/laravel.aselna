<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
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
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'eBay Listing Item']);

        /* get active store jobs*/
        $store_ids = Common::getActiveStoreJobs('EbayListingItemSyncJob','ebay');
        /* get active store jobs*/

        $query = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'ebay');
        })->where('store_status','<>',0);

        if(!empty($store_ids)) {
            $query->whereIn('id', $store_ids);
        }

        $ebay_store_ids = $query->pluck('id');

        Log::info('Ebay store ids: ' . json_encode($ebay_store_ids));

        if (env('APP_ENV') === 'local') {
//            $ebay_store_ids = ['1968772b-cc55-d4e9-a126-7e81019be976'];
        }

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
                ->whereIn('listing_item.status', [1, 2])
//                ->where(function ($query) {
//                    return $query
//                        ->where('listing_item.creation_retry_count', '<', 5)->orWhereNull('listing_item.creation_retry_count');
//                })
                ->whereNotIn('listing_item.sync_status', [
                    ListingSyncStatus::CreationError,
                    ListingSyncStatus::Synchronizing,
                ])
                ->orderBy('listing_item.created_at', 'asc')
                ->limit(100)
                ->get();

//            Log::info("Sql Query: " . $listingItemLinks->toSql());

//            Log::info("Listing Item Links Query: " . $listingItemLinks);
//            continue;

            $this->syncListingItem($listingItemLinks, $store);
        }

    }

    private function syncListingItem($listingItemLinks, $store)
    {
        $skuArray = [];
        $minDate = null;
        foreach ($listingItemLinks as $listingItemLink) {
            $itemDetail = $listingItemLink->listingItem;
            if ($itemDetail->store_sku != null) continue;


            if ($itemDetail->created_at){
                $minDate = $minDate == null ? $itemDetail->created_at : ($itemDetail->created_at < $minDate ? $itemDetail->created_at : $minDate);
            }


//            Log::info(("Diff In Date " . $itemDetail->created_at->diffInDays($minDate)));
            if (isset($minDate) && $itemDetail->created_at && $minDate->diffInDays($itemDetail->created_at) > 114) {
                Log::info("Listing item diff greater than 120 days - " . $itemDetail->sku);
                continue;
            }

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

        if (env('APP_ENV') === 'local') {
//            $skuArray = ['A16-5GLVBQ','Z96-P8XA0U','V65-DNWS7X','K11-N3MQS4','C49-VIVTG9','K34-RREHUR'];
//            $skuArray = ['A16-5GLVBQ','Z96-P8XA0U','V65-DNWS7X'];
//            $skuArray = ['K11-N3MQS4','C49-VIVTG9','K34-RREHUR','B90-RHZAJ9','E56-XAGDPL','C10-IOXWOQ'];
        }

        try {
            Log::info("Calling Service");

            $clientId = env('EBAY_CLIENT_ID');
            $clientSecret = env('EBAY_CLIENT_SECRET');
            $redirectUri = env('EBAY_REDIRECT_URI');

            $ebayAuthService = new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
            $response = $ebayAuthService->getListingItems($skuArray, $minDate);
            Log::info("Ebay getListingItems response: " . json_encode($response));

            if (isset($response) && isset($response['Ack']) && $response['Ack'] == 'Success') {
                $itemSkuArray = [];
                if (isset($response['ItemArray']) && count($response['ItemArray']['Item'] ?? []) > 0) {
                    $count = (int)$response['ReturnedItemCountActual'];
                    if ($count == 1) {
                        $response['ItemArray']['Item'] = [$response['ItemArray']['Item']];
                    }
                    foreach ($response['ItemArray']['Item'] as $item) {
                        Log::info("Processing item: " . json_encode($item));
                        if ($item['SKU'] == null || $item['SKU'] == '') {
                            Log::info("Item SKU is null or empty, skipping item.");
                            continue;
                        }
                        $itemSkuArray[] = $item['SKU'];
                        $itemDetail = ListingItem::where('sku', $item['SKU'])->where(function ($query) {
                            return $query->whereNull('store_sku')
                                ->orWhere('store_sku', '');
                        })->first();
                        //foreach ($itemDetails as $itemDetail) {
                            if ($itemDetail) {
                                if (isset($item['ItemID']) && $item['ItemID'] != '') {
                                    $itemDetail->update([
                                        'store_sku' => $item['ItemID'],
                                        'sync_status' => ListingSyncStatus::Synchronized,
                                    ]);
                                    Log::info("Updated item with SKU: " . $item['SKU'] . " and ItemID: " . $item['ItemID']);
                                } else
                                {

                                    $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                    if ($date->diffInDays(now()) >= 5) {
                                        Log::info("Listing item older than 5 days - " . $itemDetail->sku);
                                        $itemDetail->update([
                                            'status' => ListingStatus::Deleted,
                                        ]);
                                    }
//                                else{
//                                    $retry_count = $itemDetail->creation_retry_count ?? 0;
//                                    $itemDetail->update([
//                                        'creation_retry_count' => $retry_count + 1,
//                                    ]);
//                                }
                                }
                            } else {
                                Log::info("No item found for SKU: " . $item['SKU']);
                            }
                        //}
                    }
                }

                if (isset($response['ReturnedItemCountActual']) && ((int)$response['ReturnedItemCountActual']) < count($skuArray)) {
                    foreach ($skuArray as $sku) {
                        if (!in_array($sku, $itemSkuArray)) {
                            $itemDetails = ListingItem::where('sku', $sku)->get();
                            foreach ($itemDetails as $item) {
                                Log::info("Item with SKU: " . $sku . " not found in eBay response, marking as Deleted.");
                                $date = \Carbon\Carbon::parse($item->created_at);
                                if ($date->diffInDays(now()) >= 5) {
                                    Log::info("Listing item older than 5 days and not synced - " . $item->sku);
                                    $item->update([
                                        'status' => ListingStatus::Deleted,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            Log::error('Error syncing Ebay listing item: ' . $e->getMessage(), [
                'store_id' => $store->id,
            ]);
        }
    }
}
