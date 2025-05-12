<?php

namespace App\Jobs;

use App\Models\ListingItemDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ListingItemDataSyncJob implements ShouldQueue
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
        $listingItemDataQ = DB::connection('easyesale_db')->table('cea_listing_item_detail')
            ->orderBy('id');

        if (env('APP_ENV') == 'local') {
            Log::info("Came HEre");
            $listingItemDataQ->whereRaw('listing_item_id in (select id from oydera_listing_item where id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\'))');
//            $listingItemDataQ->whereRaw('listing_item_id in (select id from oydera_listing_item where store_product_id in (select id from oydera_store_product where product_id in (select id from oydera_product where oem_generic = 2)))');
        } else {
            // get Last 2 years only
            $listingItemDataQ->where('create_ts', '>=', now()->subYears(2));
        }

        $listingItemDataQ->chunk(1000, function ($listingItemDatas) {
            foreach ($listingItemDatas as $listingItemData) {
                $this->createListingItemData($listingItemData);
            }
        });
    }

    private function createListingItemData(mixed $listingItemData)
    {
        ListingItemDetail::updateOrCreate([
            'id' => $listingItemData->id
        ], [
            'id' => $listingItemData->id,
            'sku' => $listingItemData->sku,
            'title' => $listingItemData->title,
            'upc' => $listingItemData->upc,
            'url' => $listingItemData->url,
            'store_sku' => $listingItemData->store_sku,
            'kicked' => $listingItemData->kicked,
            'replaced_id' => $listingItemData->replaced_id,
            'device_id' => $listingItemData->device_id,
            'device_carrier_id' => $listingItemData->device_carrier_id,
            'listing_item_id' => $listingItemData->listing_item_id,
            'last_sale_date' => $listingItemData->last_sale_date,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
