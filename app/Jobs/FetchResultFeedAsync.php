<?php

namespace App\Jobs;

use App\Models\ListingFeed;
use App\Models\ScheduledJob;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\CriticalErrorService;
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
     * Stop waiting for a result this many hours after the feed was submitted and mark it ERROR.
     * Walmart's compliance / AI review alone can take up to 24 hours.
     */
    const MAX_WAIT_HOURS = 72;

    /** Check on every run during the first hours after submission ... */
    const FAST_CHECK_HOURS = 4;

    /** ... and after that at most once per this many minutes. */
    const SLOW_CHECK_MINUTES = 60;

    /** Feeds checked per run, and expired feeds timed out per run. */
    const FEEDS_PER_RUN = 20;

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

            $feedAge = 'COALESCE(listing_feeds.submit_ts, listing_feeds.created_at)';

            // Feeds still waiting for a result, within MAX_WAIT_HOURS of submission. After the first
            // FAST_CHECK_HOURS a feed is checked at most every SLOW_CHECK_MINUTES. Least recently
            // checked first, so every waiting feed gets its turn.
            $listingFeed = ListingFeed::whereNotNull('feed_id')
                ->whereIn('status', [
                    FeedStatus::PROCESSING,
                    FeedStatus::SUBMITTED,
                ])
                ->whereRaw("$feedAge >= ?", [now()->subHours(self::MAX_WAIT_HOURS)])
                ->where(function ($query) use ($feedAge) {
                    $query->whereRaw("$feedAge >= ?", [now()->subHours(self::FAST_CHECK_HOURS)])
                        ->orWhereNull('last_status_check_ts')
                        ->orWhere('last_status_check_ts', '<=', now()->subMinutes(self::SLOW_CHECK_MINUTES));
                })
                ->orderByRaw('last_status_check_ts IS NOT NULL, last_status_check_ts ASC')
                ->limit(self::FEEDS_PER_RUN)
                ->get();

            foreach ($listingFeed as $feed) {
                $this->fetchFeed($feed);
            }

            $this->timeOutExpiredFeeds($feedAge);

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

    /**
     * Feeds past MAX_WAIT_HOURS get one last check; if still not done they are marked ERROR,
     * so they show up instead of sitting in PROCESSING forever.
     */
    private function timeOutExpiredFeeds(string $feedAge): void
    {
        $expiredFeeds = ListingFeed::whereNotNull('feed_id')
            ->whereIn('status', [
                FeedStatus::PROCESSING,
                FeedStatus::SUBMITTED,
            ])
            ->whereRaw("$feedAge < ?", [now()->subHours(self::MAX_WAIT_HOURS)])
            ->orderByRaw("$feedAge ASC")
            ->limit(self::FEEDS_PER_RUN)
            ->get();

        foreach ($expiredFeeds as $feed) {
            $this->fetchFeed($feed);
            $feed->refresh();

            if (in_array($feed->status, [FeedStatus::PROCESSING, FeedStatus::SUBMITTED])) {
                Log::warning('Result feed timed out for feed ID: ' . $feed->id);
                $feed->update([
                    'status' => FeedStatus::ERROR,
                    'error_text' => 'Timed out: the marketplace gave no final result within ' . self::MAX_WAIT_HOURS
                        . ' hours of submission (checked ' . ($feed->status_check_count ?? 0) . ' times).',
                ]);
            }
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
