<?php

namespace App\Jobs;

use App\Models\ListingItemLink;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingItemLinkSyncJob implements ShouldQueue
{
    use Queueable;

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
        $listingItemLinksQ = DB::connection('easyesale_db')->table('listing_item_link')
            ->orderBy('id');

        if (env('APP_ENV') !== 'local') {
            $listingItemLinksQ->whereRaw('item_id in (select id from oydera_listing_item where store_product_id in (select id from oydera_store_product where product_id in (select id from oydera_product where oem_generic = 2)))');
        }

        $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
                foreach ($listingItemLinks as $listingItemLink) {
                    $this->createListingItemLink($listingItemLink);
                }
            });
    }

    private function createListingItemLink(mixed $listingItemLink)
    {
        Log::info('ListingItemLinkSyncJob: ' . $listingItemLink->id);
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
