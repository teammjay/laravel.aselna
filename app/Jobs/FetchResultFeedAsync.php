<?php

namespace App\Jobs;

use App\Models\ListingFeed;
use App\Models\ScheduledJob;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\CriticalErrorService;
use Google\Service\Classroom\Feed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Constants\FeedStatus;
use App\Jobs\Concerns\TrackableJob;

class FetchResultFeedAsync implements ShouldQueue
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
        try {
            Log::info('ResultFeedSync command started');
            Log::info("get_current_user() : " . get_current_user());
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Result Feed']);

            ScheduledJob::where('class_name', __CLASS__)
                ->update(['last_run' => now()]);

            $listingFeed = ListingFeed::whereNotNull('feed_id')
                ->whereIn('status', [
                    FeedStatus::PROCESSING,
                    FeedStatus::SUBMITTED,
                ])
                ->orderBy('submit_ts', 'desc')
                ->where(function ($query) {
                    $query->whereNull('status_check_count')
                          ->orWhere('status_check_count', '<', 5);
                })
                ->limit(5)
                ->get();

            foreach ($listingFeed as $feed) {
                $this->fetchFeed($feed);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
        }
    }

    private function fetchFeed(mixed $feed)
    {
        try {
            Log::info('Fetching result feed for feed ID: ' . $feed->id);
            $listingId = $feed->listing_id;

            $storeType = $feed->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);

            // The marketplace service's getResultFeed() is now responsible for
            // updating status, counts, and error_text on the ListingFeed.
            $marketplaceService->getResultFeed($listingId, $feed->id);

            // Always update the check tracking regardless of result
            $feed->refresh();
            $feed->status_check_count = ($feed->status_check_count ?? 0) + 1;
            $feed->last_status_check_ts = now();
            $feed->save();

        } catch (\Throwable $e) {

            $extraInfo = [
                'feed_id' => $feed->id
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

            CriticalErrorService::report('Fetch Result Feed Job Failed', [
                'marketplace' => $feed->store->storeType->name ?? 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ],$extraInfo);

            Log::error('Error fetching result feed: ' . $e->getMessage());
            $feed->status = FeedStatus::FAILED;
            $feed->error_text = 'Result fetch failed: ' . $e->getMessage();
            $feed->status_check_count = ($feed->status_check_count ?? 0) + 1;
            $feed->last_status_check_ts = now();
            $feed->save();
        }


    }
}
