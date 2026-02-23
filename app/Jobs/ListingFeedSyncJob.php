<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ListingFeedSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 4000;

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
        $this->updateTrackingRecord(['tags' => 'Listing Feed']);

        try {
            $allFeedsQ = DB::connection('easyesale_db')
                ->table('listing_feed')
                ->orderBy('id');

            if (env('APP_ENV') === 'local') {
                $allFeedsQ->whereNotNull('listing_id');
            }

            $allFeedsQ->chunk(1000, function ($allFeeds) {
                foreach ($allFeeds as $feed) {
                    $this->createListingFeed($feed);
                }
            });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Feed Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function createListingFeed($feed)
    {
        if (isset($feed->listing_id) && !Listing::where('id', $feed->listing_id)->exists()) {
            return;
        }
        ListingFeed::updateOrCreate(
            ['id' => $feed->id],
            [
                'id' => $feed->id,
                'file_id' => $feed->file_id,
                'listing_id' => $feed->listing_id,
                'number_of_attempts' => $feed->number_of_attempts,
                'status' => $feed->status,
                'error_text' => $feed->error_text,
                'feed_id' => $feed->feed_id,
                'submit_ts' => $feed->submit_ts,
                'errors' => $feed->errors,
                'last_status_check_ts' => $feed->last_status_check_ts,
                'status_check_count' => $feed->status_check_count,
                'processing_count' => $feed->processing_count,
                'success_count' => $feed->success_count,
                'fail_count' => $feed->fail_count,
                'warning_count' => $feed->warning_count,
                'number_of_items' => $feed->number_of_items,
                'client' => $feed->client,
                'file_reference_id' => $feed->file_reference_id,
                'store_feed_type' => $feed->store_feed_type,
                'store_id' => $feed->store_id,
                'feed_type' => $feed->feed_type,
                'response_file_id' => $feed->response_file_id,
                'feed_execution_delay' => $feed->feed_execution_delay,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
