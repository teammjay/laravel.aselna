<?php

namespace App\Jobs\Walmart;

use App\Constants\ListingStatus;
use App\Constants\ListingSyncStatus;
use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Models\ListingItemLink;
use App\Models\Store;
use App\Services\Auth\WalmartAuthService;
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now()]);
        Log::info('WalmartExistingListingItemSyncJob started');

        $store_ids = Common::getActiveStoreJobs('WalmartListingItemSyncJob','walmart');

        $query = Store::where('store_type_id', function ($subquery) {
            return $subquery->select('id')
                ->from('store_type')
                ->where('code', 'walmart');
        })->where('store_status','<>',0);

        if(!empty($store_ids)) {
            $query->whereIn('id', $store_ids);
        }
        $walmart_store_ids = $query->pluck('id');
        Log::info('Walmart store ids: ' . json_encode($walmart_store_ids));

        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Walmart']);

        ListingItemLink::query()
            ->with(['listing','listingItem'])
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $walmart_store_ids)
            //->whereNot('listing_item.status', ListingStatus::Deleted)
            ->whereNotNull('listing_item.store_sku')
            ->orderBy('listing_item.created_at','asc')
            ->chunk(100, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    Log::info('Syncing Walmart listing item: ' . $listingItemLink->item_id);
                    $this->syncListingItem($listingItemLink);
                }
            });

        $this->updateTrackingRecord([
            'status' => 'completed',
            'finished_at' => now()
        ]);
    }

    private function syncListingItem($listingItemLink)
    {
        $store = $listingItemLink->listing->store;
        $itemDetail = $listingItemLink->listingItem;

        try {
            Log::info("Calling Service");
            $authService = new WalmartAuthService($store->seller_id, $store->secret_key);
            $response = $authService->getItemSearch($itemDetail->upc);

            if(!isset($response['error'])) {
                if (isset($response['items'][0])) {
                    $item = $response['items'][0];
                    if (isset($item['itemId']) && $item['itemId'] != '') {
                        Log::info("Update Listing Item with " . $item['itemId']);
                        $itemDetail->update([
                            'store_sku' => $item['itemId'],
                            'sync_status' => ListingSyncStatus::Synchronized,
                        ]);
                    }
                }else{
                    $itemDetail->update([
                        'status' => ListingStatus::Deleted,
                    ]);
                }
            }

        } catch (\Throwable $e) {

            $extraInfo = [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'upc' => $itemDetail->upc,
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

            $itemDetail->update([
                'sync_status' => ListingSyncStatus::CreationError,
            ]);

            Log::error('Error syncing Walmart listing item: ' . $e->getMessage(), [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'upc' => $itemDetail->upc,
            ]);

            CriticalErrorService::report('Error syncing Walmart listing item', [
                'marketplace' => ucfirst($store->name),
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],['store_name' => $store->name]);
        }
    }
}
