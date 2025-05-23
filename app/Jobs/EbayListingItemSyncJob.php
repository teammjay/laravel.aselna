<?php

namespace App\Jobs;

use App\Constants\FeedStatus;
use App\Constants\ListingSyncStatus;
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

        //join listing_item_link and listing_item and listing
        ListingItemLink::query()
            ->join('listing_item', 'listing_item_link.item_id', '=', 'listing_item.id')
            ->join('listing', 'listing_item_link.listing_id', '=', 'listing.id')
            ->whereIn('listing.store_id', $ebay_store_ids)
            ->whereNull('listing_item.store_sku')
            ->whereIn('listing_item.sync_status', [
                ListingSyncStatus::Synchronizing,
                ListingSyncStatus::Unsynchronize,
            ])
            ->whereIn('listing.id', function ($subquery) use ($ebay_store_ids) {
                return $subquery->select('listing_id')->from('listing_feeds')->whereIn('store_id', $ebay_store_ids)->whereIn('status', [
                    FeedStatus::PROCESSED
                ]);
            })
            ->chunk(100, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    Log::info('Syncing Ebay listing item: ' . $listingItemLink->item_id);
                    $this->syncListingItem($listingItemLink);
                }
            });
    }

    private function syncListingItem(mixed $listingItemLink)
    {
        $store = $listingItemLink->listing->store;
        $itemDetail = $listingItemLink->listingItem;
        Log::info("Listing Item Sku " . $itemDetail->store_sku);
        if($itemDetail->store_sku != null) return;

        try {
            Log::info("Calling Service");
            $clientId = env('EBAY_CLIENT_ID');
            $clientSecret = env('EBAY_CLIENT_SECRET');
            $redirectUri = env('EBAY_REDIRECT_URI');

            $ebayAuthService =  new EbayAuthService($clientId, $clientSecret, $redirectUri, $store->user_id);
            $response = $ebayAuthService->getListingItemId($itemDetail->sku);
            Log::info('Response ::' . json_encode($response));
            if (isset($response['Ack']) && $response['Ack'] == 'Success' ) {
                if(isset($response['Item']['ItemID'])) {
                    $itemDetail->update([
                        'store_sku' => $response['Item']['ItemID'],
                        'sync_status' => ListingSyncStatus::Synchronized,
                    ]);
                }
            }

        } catch (\Exception $e) {
            $itemDetail->update([
                'sync_status' => ListingSyncStatus::CreationError,
            ]);
            Log::error('Error syncing Ebay listing item: ' . $e->getMessage(), [
                'listing_item_id' => $itemDetail->id,
                'store_id' => $store->id,
                'sku' => $itemDetail->sku,
            ]);
        }
    }
}
