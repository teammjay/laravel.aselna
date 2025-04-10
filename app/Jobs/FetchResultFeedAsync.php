<?php

namespace App\Jobs;

use App\Models\ListingFeed;
use App\Models\ScheduledJob;
use App\Services\Marketplace\MarketplaceServiceFactory;
use Google\Service\Classroom\Feed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Constants\FeedStatus;

class FetchResultFeedAsync implements ShouldQueue
{
    use Queueable;

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
        ScheduledJob::where('class_name', __CLASS__)
            ->update(['last_run' => now()]);

        $listingFeed = ListingFeed::whereNotNull('feed_id')
            ->whereIn('status', [
                FeedStatus::CREATED,
                FeedStatus::PROCESSING,
                FeedStatus::FAILED,
            ])
            ->orderBy('submit_ts', 'desc')
            ->where('status_check_count', '<', 3)
            ->limit(5)
            ->get();

        foreach ($listingFeed as $feed) {
            $this->fetchFeed($feed);
        }
    }

    private function fetchFeed(mixed $feed)
    {
        try {
            $listingId = $feed->listing_id;

            $storeType = $feed->store->storeType->name;

            $marketplaceService = MarketplaceServiceFactory::create($storeType);
            $marketplaceService->getResultFeed($listingId, $feed->id);
            $feed->status = FeedStatus::PROCESSED;
            $feed->save();
        } catch (\Exception $e) {
            Log::error('Error fetching result feed: ' . $e->getMessage());
            $feed->status = FeedStatus::FAILED;
            $feed->status_check_count = ($feed->status_check_count ?? 0) + 1;
            $feed->save();
        }


    }
}
