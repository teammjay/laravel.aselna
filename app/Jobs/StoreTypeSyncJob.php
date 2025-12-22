<?php

namespace App\Jobs;

use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class StoreTypeSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Store Type']);

        try {
            $storeTypes = DB::connection('easyesale_db')->table('store_type')->cursor();
            foreach ($storeTypes as $storeType) {
                $this->createStoreType($storeType);
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('Store Type Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createStoreType(mixed $storeType)
    {
        StoreType::updateOrCreate([
            'id' => $storeType->id
        ],[
            'id' => $storeType->id,
            'code' => $storeType->code,
            'name' => $storeType->name,
            'max_title_length' => $storeType->max_title_length,
            'max_variation_name_length' => $storeType->max_variation_name_length,
            'max_listing_variants' => $storeType->max_listing_variants,
            'max_random_title_length' => $storeType->max_random_title_length,
            'acknowledge_order' => $storeType->acknowledge_order,
            'min_image_width' => $storeType->min_image_width,
            'min_image_height' => $storeType->min_image_height,
            'order_shipment_control_backward_days' => $storeType->order_shipment_control_backward_days,
            'create_image_per_listing' => $storeType->create_image_per_listing,
        ]);
    }
}
