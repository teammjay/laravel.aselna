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
        Log::info('ResultFeedSync command started');
        Log::info("get_current_user() : " . get_current_user());
        $this->updateTrackingRecord(['tags' => 'Result Feed']);

        ScheduledJob::where('class_name', __CLASS__)
            ->update(['last_run' => now()]);

        $listingFeed = ListingFeed::whereNotNull('feed_id')
            ->whereIn('status', [
                FeedStatus::PROCESSING,
                FeedStatus::FAILED,
            ])
            ->orderBy('submit_ts', 'desc')
            ->where(function ($query) {
                $query->whereNull('status_check_count')
                      ->orWhere('status_check_count', '<', 3);
            })
            ->limit(5)
            ->get();

        foreach ($listingFeed as $feed) {
            $this->fetchFeed($feed);
        }
    }

    private function fetchFeed(mixed $feed)
    {
        try {
            Log::info('Fetching result feed for feed ID: ' . $feed->id);
            $listingId = $feed->listing_id;

            $storeType = $feed->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);
            $response = $marketplaceService->getResultFeed($listingId, $feed->id);
            if (isset($response)) {
                $feed->status = FeedStatus::PROCESSED;
                $feed->save();
            }
        } catch (\Throwable $e) {

            $extraInfo = [
                'feed_id' => $feed->id
            ];

            \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

            CriticalErrorService::report('Fetch Result Feed Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ],$extraInfo);

            Log::error('Error fetching result feed: ' . $e->getMessage());
            $feed->status = FeedStatus::FAILED;
            $feed->status_check_count = ($feed->status_check_count ?? 0) + 1;
            $feed->save();
        }


    }
}
