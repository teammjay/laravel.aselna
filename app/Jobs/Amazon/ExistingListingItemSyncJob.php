<?php

namespace App\Jobs\Amazon;

use App\Constants\ListingStatus;
use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\AmazonAuthService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExistingListingItemSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 7200;

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
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now()]);

            Log::info('AmazonExistingListingItemSyncJob started');

            $store_ids = Common::getActiveStoreJobs('AmazonListingItemSyncJob', 'Amazon');

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
            if ($amazon_store_ids) {
                foreach ($amazon_store_ids as $store_id) {
                    Log::info('Fetching marketplace ids for store id: ' . $store_id);
                    $store = Store::findOrFail($store_id);
                    $amazonAuthService = new AmazonAuthService($store->seller_id, $store->secret_key, $store->api_key);
                    $marketPlaceId[$store_id] = $amazonAuthService->getMarketplaceIDs();
                }
            }

            $this->updateTrackingRecord(['tags' => 'Amazon']);


            ListingItemLink::query()
                ->with(['listing', 'listingItem'])
                ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
                ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
                ->whereIn('listing.store_id', $amazon_store_ids)
                ->whereNotNull('listing_item.store_sku')
                ->whereNot('listing_item.status', ListingStatus::Deleted)
                ->orderBy('listing_item.created_at', 'asc')
                ->chunk(20, function ($listingItemLinks) use ($marketPlaceId) {
                    $itemSkus = [];
                    foreach ($listingItemLinks as $listingItemLink) {
                        Log::info('Existing Syncing Amazon listing item: ' . $listingItemLink->item_id);
                        //$this->syncListingItem($listingItemLink);
                        $store = $listingItemLink->listing->store;
                        $store_id = $store->id;
                        $itemDetail = $listingItemLink->listingItem;

                        Log::info("Existing Amazon Listing Item Sku " . $itemDetail->sku);
                        $itemSkus[$store_id][] = trim($itemDetail->sku);
                    }

                    if ($itemSkus) {
                        Log::info("Syncing Existing Amazon Listing Items for stores :: ");
                        foreach ($itemSkus as $storeId => $skus) {
                            foreach (array_chunk($skus, 10) as $chunk) {
                                $this->syncListingItems($storeId, $chunk, $marketPlaceId);
                            }
                        }
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Exception $e) {
            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Amazon Existing Listing Item Sync Job Failed', [
                'marketplace' => 'Amazon',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error in Amazon Existing Listing Item Sync Job: ' . $e->getMessage());
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
                Log::info('Skus to be synced :: ' . json_encode($items));
                $resultItems = $amazonAuthService->fetchListingItems($store->user_id,$items,$marketplaceIds);
                Log::info("Result Items :: " . json_encode($resultItems));

                $itemFetch = [];
                if($resultItems){
                    foreach($resultItems as $item){
                        if(isset($item['sku']) && $item['sku'] != ''){
                            $itemDetail = ListingItem::where('sku',$item['sku'])->first();
                            if($itemDetail){
                                if(isset($item['summaries'])) {
                                    $summary = $item['summaries'][0];
                                    if(isset($summary['asin'])) {
                                        $itemFetch[] = $item['sku'];
                                    }
                                }
                            }
                        }
                    }
                }
                Log::info('Fetched Item SKUs :: ' . json_encode($itemFetch));

                if($itemFetch) {
                    foreach ($items as $item) {
                        if (!in_array($item, $itemFetch)) {
                            $itemDetail = ListingItem::where('sku', $item)->first();

                            $itemDetail->update([
                                'status' =>ListingStatus::Deleted,
                            ]);
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
}
