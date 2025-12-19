<?php

namespace App\Jobs;

use App\Models\StoreType;
use App\Models\StoreTypeListing;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class StoreTypeListingSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 6000;

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
        $this->updateTrackingRecord(['tags' => 'Store Type Listing']);

        try {
            $allStoreListingQ = DB::connection('easyesale_db')->table('channel_listing')
                ->orderBy('id');

            $allStoreListingQ->chunk(1000, function ($allStoreListing) {
                foreach ($allStoreListing as $storeListing) {
                    $this->createStoreListing($storeListing);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Store Type Listing Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createStoreListing($storeListing)
    {
        StoreTypeListing::updateOrCreate([
            'id' => $storeListing->id,
        ], [
            'id' => $storeListing->id,
            'seller_id' => $storeListing->seller_id,
            'sku' => $storeListing->sku,
            'product_name' => $storeListing->product_name,
            'description' => $storeListing->description,
            'product_category' => $storeListing->product_category,
            'price' => $storeListing->price,
            'publish_status' => $storeListing->publish_status,
            'status_change_reason' => $storeListing->status_change_reason,
            'inventory_count' => $storeListing->inventory_count,
            'wpid' => $storeListing->wpid,
            'item_id' => $storeListing->item_id,
            'upc' => $storeListing->upc,
            'shelf_name' => $storeListing->shelf_name,
            'primary_catalog_path' => $storeListing->primary_catalog_path,
            'item_page_url' => $storeListing->item_page_url,
            'review_count' => $storeListing->review_count,
            'avarage_rating' => $storeListing->avarage_rating,
            'primary_image_url' => $storeListing->primary_image_url,
            'store_id' => $storeListing->store_id,
            'ship_price' => $storeListing->ship_price,
            'buy_box_price' => $storeListing->buy_box_price,
            'buy_box_shipping_price' => $storeListing->buy_box_shipping_price,
            'seller_buy_box_winner' => $storeListing->seller_buy_box_winner,
            'buybox_winner_id' => $storeListing->buybox_winner_id,
            'has_offers' => $storeListing->has_offers,
            'status' => $storeListing->status,
            'quality_field_name' => $storeListing->quality_field_name,
            'quality_alert_type' => $storeListing->quality_alert_type,
            'quality_current_value' => $storeListing->quality_current_value,
            'quality_last_updated' => $storeListing->quality_last_updated,
            'quality_alert_name' => $storeListing->quality_alert_name,
            'quality_explanation' => $storeListing->quality_explanation,
            'item_condition ' => $storeListing->item_condition,
            'removed_externally' => $storeListing->removed_externally,
            'report_feed_id' => $storeListing->report_feed_id,
            'merged_with_another_listing' => $storeListing->merged_with_another_listing,
            'previous_item_id' => $storeListing->previous_item_id,
        ]);
    }
}
