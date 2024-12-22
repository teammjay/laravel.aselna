<?php

namespace App\Jobs;

use App\Models\ListingItemDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ListingItemDataSyncJob implements ShouldQueue
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
        $listingItemDatas = DB::connection('easyesale_db')->table('cea_listing_item_detail')->cursor();
        foreach ($listingItemDatas as $listingItemData) {
            $this->createListingItemData($listingItemData);
        }
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
