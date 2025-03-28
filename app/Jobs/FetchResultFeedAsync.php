<?php

namespace App\Jobs;

use App\Models\ListingFeed;
use App\Models\ScheduledJob;
use App\Services\Marketplace\MarketplaceServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        ScheduledJob::where('class_name', __CLASS__)
            ->update(['last_run' => now()]);

        $listingFeed = ListingFeed::whereNotNull('feed_id')
            ->whereNull('number_of_attempts')->orWhere('number_of_attempts', '<', 1)
            ->limit(5)
            ->get();

        foreach ($listingFeed as $feed) {
            $this->fetchFeed($feed);
        }
    }

    private function fetchFeed(mixed $feed)
    {
        $listingId = $feed->listing_id;

        $storeType = $feed->store->storeType->name;

        $marketplaceService = MarketplaceServiceFactory::create($storeType);
        $marketplaceService->getResultFeed($listingId);
    }
}
