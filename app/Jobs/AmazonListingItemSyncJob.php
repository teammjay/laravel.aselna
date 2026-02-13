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
use App\Services\Auth\AmazonAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
//use const http\Client\Curl\FEATURES;

class AmazonListingItemSyncJob implements ShouldQueue
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
        try {
            Log::info('AmazonListingItemSyncJob started');
            $this->updateTrackingRecord(['tags' => 'Amazon ListingItem']);

            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('AmazonListingItemSyncJob', 'amazon');
            /* get active store jobs*/

            $query = Store::where('store_type_id', function ($subquery) {
                return $subquery->select('id')
                    ->from('store_type')
                    ->where('code', 'amazon');
            })->where('store_status', '<>', 0);

            if (!empty($store_ids)) {
                $query->whereIn('id', $store_ids);
            }

            $amazon_store_ids = $query->pluck('id');
            Log::info('Amazon store ids: ' . json_encode($amazon_store_ids));

            $marketPlaceId = [];
            if($amazon_store_ids){
                foreach($amazon_store_ids as $store_id) {
                    $store = Store::findOrFail($store_id);
                    $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key, $store->api_key);
                    $marketPlaceId[$store_id] = $amazonAuthService->getMarketplaceIDs();
                }
            }

            Log::info('Amazon marketplace ids: ' . json_encode($marketPlaceId));

            //join listing_item_link and listing_item and listing
            ListingItemLink::query()
                ->with(['listing', 'listingItem'])
                ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
                ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
                ->whereIn('listing.store_id', $amazon_store_ids)
                ->whereRaw("COALESCE(listing_item.store_sku, '') = ''")
                ->whereIn('listing_item.status', [1, 2])
                ->whereRaw('(listing_item.creation_retry_count < 5 OR listing_item.creation_retry_count IS NULL)')
                ->whereIn('listing_item.sync_status', [
                    ListingSyncStatus::CreationError,
                    ListingSyncStatus::Synchronizing,
                    ListingSyncStatus::Unsynchronize,
                ])
                /*->whereNotExists(function ($subquery) use ($amazon_store_ids) {
                    $subquery->selectRaw(1)
                        ->from('listing_feeds')
                        ->whereColumn('listing_feeds.listing_id', 'listing.id')
                        ->whereIn('store_id', $amazon_store_ids);
                        //->where('status', FeedStatus::PROCESSING);
                })*/
                ->orderBy('listing_item.created_at', 'desc')
                ->chunk(20, function ($listingItemLinks) use($marketPlaceId) {
                    $itemSkus = [];
                    foreach ($listingItemLinks as $listingItemLink) {
                        Log::info('Syncing Amazon listing item: ' . $listingItemLink->item_id);
                        //$this->syncListingItem($listingItemLink);
                        $store = $listingItemLink->listing->store;
                        $store_id = $store->id;
                        $itemDetail = $listingItemLink->listingItem;
                        if ($itemDetail->store_sku != null) return;

                        Log::info("Listing Item Sku " . $itemDetail->sku);
                        $itemSkus[$store_id][] = trim($itemDetail->sku);

                    }
                    Log::info("Listing Item Sku list " . json_encode($itemSkus));
                    //$this->syncListingItems($itemSkus);

                    if($itemSkus) {
                        foreach ($itemSkus as $storeId => $skus) {
                            foreach (array_chunk($skus, 10) as $chunk) {
                                $this->syncListingItems($storeId, $chunk, $marketPlaceId);
                            }
                        }
                    }
                });


        }catch (\Throwable $e) {

            CriticalErrorService::report('Amazon Listing Item Sync Job Failed', [
                'marketplace' => 'Amazon',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }

    private function syncListingItems($store_id, $items, $marketPlaceId)
    {
        try {

                $store = Store::findOrFail($store_id);
                if($items){
                    $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key,$store->api_key);
                    if(!isset($marketPlaceId[$store_id])){
                        return false;
                    }

                    Log::info("Amazon marketplace ids :: " . json_encode($marketPlaceId[$store_id]));
                    $marketplaceIds = $marketPlaceId[$store_id];
                    $resultItems = $amazonAuthService->fetchListingItems($store->user_id,$items,$marketplaceIds);
                    Log::info("Result Items :: " . json_encode($resultItems));

                    $itemFetch = [];
                    if(!empty($resultItems)){
                        foreach($resultItems as $item){
                            if(isset($item['sku']) && $item['sku'] != ''){
                                $itemFetch[] = $item['sku'];
                                Log::info("Listing Item :" . json_encode($item));
                                $itemDetail = ListingItem::where('sku',$item['sku'])->first();
                                if($itemDetail){
                                    if(isset($item['summaries'])) {
                                        $summary = $item['summaries'][0];
                                        Log::info("Listing Item Updated :" . $item['sku'].'->'.$summary['asin']);
                                        $itemDetail->update([
                                            'store_sku' => $summary['asin'],
                                            'sync_status' => ListingSyncStatus::Synchronized,
                                        ]);
                                    }else{
                                        $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                        if ($date->diffInDays(now()) >= 5) {
                                            Log::info("Listing item older than 5 days - ".$itemDetail->sku);
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
                                }
                            }
                        }
                    }

                    if(!empty($itemFetch)) {
                        foreach ($items as $item) {
                            if (!in_array($item, $itemFetch)) {
                                $itemDetail = ListingItem::where('sku', $item)->first();

                                $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                if ($date->diffInDays(now()) >= 5) {
                                    Log::info("Listing item older than 5 days - ".$itemDetail->sku);
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
                        }
                    }
                }

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Amazon Listing Item Sync Job Failed', [
                'marketplace' => 'Amazon',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['itemSkus' => $items]);

            Log::error('Error syncing Amazon listing item: ' . $e->getMessage(), [
                'skus' => $items,
            ]);
        }
    }

    private function syncListingItemsOld($itemSkus)
    {
        try {

            if($itemSkus){
                foreach($itemSkus as $store_id=>$items){
                    $store = Store::findOrFail($store_id);
                    if($items){
                        $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key,$store->api_key);
                        $resultItems = $amazonAuthService->fetchListingItems($store->user_id,$items);
                        Log::info("Result Items :: " . json_encode($resultItems));

                        $itemFetch = [];
                        if($resultItems){
                            foreach($resultItems as $item){
                                if(isset($item['sku']) && $item['sku'] != ''){
                                    $itemFetch[] = $item['sku'];
                                    $itemDetail = ListingItem::where('sku',$item['sku'])->first();
                                    if($itemDetail){
                                        if(isset($item['summaries'])) {
                                            $summary = $item['summaries'][0];
                                            Log::info("Listing Item Updated :" . $item['sku'].'->'.$summary['asin']);
                                            $itemDetail->update([
                                                'store_sku' => $summary['asin'],
                                                'sync_status' => ListingSyncStatus::Synchronized,
                                            ]);
                                        }else{
                                            $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                            if ($date->diffInDays(now()) >= 5) {
                                                Log::info("Listing item older than 5 days - ".$itemDetail->sku);
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
                                    }
                                }
                            }
                        }

                        if($itemFetch) {
                            foreach ($items as $item) {
                                if (!in_array($item, $itemFetch)) {
                                    $itemDetail = ListingItem::where('sku', $item)->first();

                                    $date = \Carbon\Carbon::parse($itemDetail->created_at);
                                    if ($date->diffInDays(now()) >= 5) {
                                        Log::info("Listing item older than 5 days - ".$itemDetail->sku);
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
                            }
                        }
                    }
                }
            }

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Amazon Listing Item Sync Job Failed', [
                'marketplace' => 'Amazon',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['itemSkus' => $itemSkus]);

            Log::error('Error syncing Amazon listing item: ' . $e->getMessage(), [
                'skus' => $itemSkus,
            ]);
        }
    }

    private function syncListingItem(mixed $listingItemLink)
    {
        $store = $listingItemLink->listing->store;
        $itemDetail = $listingItemLink->listingItem;
        Log::info("Listing Item Sku " . $itemDetail->store_sku != null);
        if($itemDetail->store_sku != null) return;

        try {
            Log::info("Calling Service");
            $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key,$store->api_key);
            $response = $amazonAuthService->fetchListingInfoBySku($itemDetail->sku, $store->user_id);
            Log::info('Summary ::' . json_encode($response['summaries']));
            if ($response['summaries']) {
                $summary = $response['summaries'][0];
                $itemDetail->update([
                    'store_sku' => $summary['asin'],
                    'sync_status' => ListingSyncStatus::Synchronized,
                ]);
            }else{
                $date = \Carbon\Carbon::parse($itemDetail->created_at);
                if ($date->diffInDays(now()) >= 5) {
                    Log::info("Listing item older than 5 days - ".$itemDetail->sku);
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

        } catch (\Throwable $e) {

            $extraInfo = [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'sku' => $itemDetail->sku,
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

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
