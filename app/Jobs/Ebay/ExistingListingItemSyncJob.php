<?php

namespace App\Jobs\Ebay;

use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Models\ListingItem;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\EbayAuthService;
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
        Log::info('EbayExistingListingItemSyncJob started');

        $store_ids = Common::getActiveStoreJobs('EbayListingItemSyncJob','ebay');

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

        $this->updateTrackingRecord(['tags' => 'eBay Existing Listing Item Sync Job']);

        ListingItemLink::query()
            ->with(['listing','listingItem'])
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $ebay_store_ids)
            ->whereNotNull('listing_item.store_sku')
            ->orderBy('listing_item.created_at','asc')
            ->chunk(100, function ($listingItemLinks) {

                $skuArray = [];
                $minDate = null;
                foreach ($listingItemLinks as $listingItemLink) {
                    Log::info('Syncing eBay listing item: ' . $listingItemLink->item_id);

                    $store = $listingItemLink->listing->store;
                    $itemDetail = $listingItemLink->listingItem;

                    if ($itemDetail->created_at){
                        $minDate = $minDate == null ? $itemDetail->created_at->subDays(1) : ($itemDetail->created_at < $minDate ? $itemDetail->created_at : $minDate);
                    }

                    if (isset($minDate) && $itemDetail->created_at && $minDate->diffInDays($itemDetail->created_at) > 114) {
                        Log::info("Listing item diff greater than 120 days - " . $itemDetail->sku);
                        continue;
                    }

                    $skuArray[$store->id][] = $itemDetail->sku;
                }
                Log::info("Listing Item Sku Array " . json_encode($skuArray));

                foreach($skuArray as $storeId=>$skus){
                    if (count($skuArray[$store->id]) == 0) {
                        Log::info("No items to sync for store: " . $store->id);
                        return;
                    }
                    $this->syncListingItem($skus,$storeId,$minDate);
                }
            });

        $this->updateTrackingRecord([
            'status' => 'completed',
            'finished_at' => now()
        ]);
    }

    private function syncListingItem($skuArray,$storeId, $minDate)
    {
        try {
            Log::info("Calling Service");

            $clientId = env('EBAY_CLIENT_ID');
            $clientSecret = env('EBAY_CLIENT_SECRET');
            $redirectUri = env('EBAY_REDIRECT_URI');

            $store = Store::findOrFail($storeId);

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

                        $itemDetail = ListingItem::where('sku', $item['SKU'])->where(function ($query) {
                            return $query->whereNotNull('store_sku')
                                ->Where('store_sku','!=', '');
                        })->first();
                        if ($itemDetail) {
                            if (isset($item['ItemID'])) {
                                $itemSkuArray[] = $item['SKU'];
                                Log::info("SKU Active: " . $item['SKU']);
                                $itemDetail->update([
                                    'status' => ListingStatus::Active,
                                    'sync_status' => ListingSyncStatus::Synchronized,
                                ]);
                            }
                        } else {
                            Log::info("SKU Missing: " . $item['SKU']);
                        }
                    }
                }

                if (isset($response['ReturnedItemCountActual']) && ((int)$response['ReturnedItemCountActual']) < count($skuArray)) {
                    foreach ($skuArray as $sku) {
                        if (!in_array($sku, $itemSkuArray)) {
                            $itemDetails = ListingItem::where('sku', $sku)->get();
                            foreach ($itemDetails as $item) {
                                Log::info("Item with SKU: " . $sku . " not found in eBay response, marking as Deleted.");
                                $item->update([
                                    'status' => ListingStatus::Deleted,
                                ]);
                            }
                        }
                    }
                }
            }

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Ebay Existing Listing Item Sync Job Failed', [
                'marketplace' => 'eBay',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_id' => $store->id]);

            Log::error('Error existing syncing Ebay listing item: ' . $e->getMessage(), [
                'store_id' => $storeId,
            ]);
        }
    }
}
