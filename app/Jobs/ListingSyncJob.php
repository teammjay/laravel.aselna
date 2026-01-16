<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ListingSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Listings']);

        try {
            $listings = DB::connection('easyesale_db')
                ->table('listing')
//            ->where('create_ts', '>=', now()->subYears(2))
                ->orderBy('id')
                ->chunk(1000, function ($listings) {
                    foreach ($listings as $listing) {
                        $this->createListing($listing);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function createListing(mixed $listing)
    {
        Listing::updateOrCreate([
            'id' => $listing->id
        ], [
            'brand_combo' => $listing->brand_combo,
            'carrier_combo' => $listing->carrier_combo,
            'chained' => $listing->chained,
            'client'    => $listing->client,
            'created_at' => now(),
            'created_by' => 'system',
            'device_id' => $listing->device_id,
            'feed_execution_delay' => $listing->feed_execution_delay,
            'feed_type' => $listing->feed_type,
            'generation_type_id' => $listing->generation_type_id,
            'id' => $listing->id,
            'include_carriers' => $listing->include_carriers,
            'manufacturer_id' => $listing->manufacturer_id,
            'preserve_title_description' => $listing->preserve_title_description,
            'status' => $listing->status,
            'store_id' => $listing->store_id,
            'updated_at' => now(),
            'updated_by' => 'system'
        ]);
    }
}
