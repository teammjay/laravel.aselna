<?php

namespace App\Jobs;

use App\Models\ListingItemLink;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ListingItemLinkSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 60000;

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
        $this->updateTrackingRecord(['tags' => 'Listing Item Link']);

        try {
            $listingItemLinksQ = DB::connection('easyesale_db')->table('listing_item_link')
                ->orderBy('id');

            if (env('APP_ENV') === 'local') {
                $listingItemLinksQ->whereRaw('item_id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\')');
//            $listingItemLinksQ->whereRaw('item_id in (select id from oydera_listing_item where store_product_id in (select id from oydera_store_product where product_id in (select id from oydera_product where oem_generic = 2)))');
            }

            $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    $this->createListingItemLink($listingItemLink);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Item Link Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createListingItemLink(mixed $listingItemLink)
    {
//        Log::info('ListingItemLinkSyncJob: ' . $listingItemLink->id);
        ListingItemLink::updateOrCreate([
            'id' => $listingItemLink->id
        ], [
            'id' => $listingItemLink->id,
            'item_id' => $listingItemLink->item_id,
            'listing_id' => $listingItemLink->listing_id,
            'feed_id' => $listingItemLink->feed_id,
            'parent_id' => $listingItemLink->parent_id,
            'client' => $listingItemLink->client,
            'replaced_id' => $listingItemLink->replaced_id,
            'order_operation_item_id' => $listingItemLink->order_operation_item_id,
            'old_price' => $listingItemLink->old_price,
            'sync_status' => $listingItemLink->sync_status,
            'feed_message_id' => $listingItemLink->feed_message_id,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
