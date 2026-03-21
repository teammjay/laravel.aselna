<?php

namespace App\Jobs;

use App\Constants\FeedStatus;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Marketplace\MarketplaceServiceFactory;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FeedGenerationJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    private $listingId;

    private $feedId;

    /**
     * Create a new job instance.
     */
    public function __construct($ListingId, $feedId = null)
    {
        $this->listingId = $ListingId;
        $this->feedId = $feedId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listingInfo = Listing::where('id', $this->listingId)->first();
        Log::info('Listing : ' . $listingInfo);

        $feedInfo = null;

        try {
            // get StoreType froms store that is inside listingfeed
            $store = $listingInfo->store;
            $storeType = $listingInfo->store->storeType->name;
            $storeName = $listingInfo->store->name;
            Log::info('Store Type: ' . $storeType);

            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => $storeName . ' ' . $storeType . ' Feed Generation']);

            $marketplaceService = MarketplaceServiceFactory::create($storeType);
            Log::info('Marketplace Service Created');

            Log::info('Generating Feed Starts');
            $created_by = auth()->user()->name ?? 'admin';
            $feedData = array(
                'listing_id' => $this->listingId,
                'status' => FeedStatus::CREATED,
                'feed_type' => $listingInfo->feed_type,
                'store_id' => $listingInfo->store_id,
                'created_by' => $created_by
            );
            $feedInfo = ListingFeed::updateOrCreate([
                'listing_id' => $this->listingId,
                'feed_type' => $listingInfo->feed_type
            ], $feedData);
            Log::info("get_current_user() : " . get_current_user());
            Log::info('Feed Data Updated' . json_encode($feedInfo));

            $marketplaceService->generateFeed($this->listingId, $feedInfo->id);
            Log::info('Feed Generated');
            $itemCount = ListingItemLink::where('listing_id', $this->listingId)->count();

            ListingFeed::where(['listing_id' => $this->listingId])->update(['number_of_items' => $itemCount]);
            ListingItemLink::where('listing_id', $this->listingId)->update(['feed_id' => $feedInfo->id]);

            if (isset($store->auto_submit_listing) && $store->auto_submit_listing == 1) {
                $marketplaceService->submitFeed($feedInfo->id, $this->listingId);
                Log::info('Feed Submitted');
            } else {
                Log::info('Auto Submit Listing is Disabled. Skipping Feed Submission.');
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

            // Mark the ListingFeed as errored so the dashboard reflects the failure
            if ($feedInfo !== null) {
                $feedInfo->update([
                    'status'     => FeedStatus::ERROR,
                    'error_text' => $e->getMessage(),
                ]);
            } else {
                // feedInfo was never created — try to mark via listing_id
                ListingFeed::where('listing_id', $this->listingId)
                    ->update([
                        'status'     => FeedStatus::ERROR,
                        'error_text' => $e->getMessage(),
                    ]);
            }

            CriticalErrorService::report('Feed Generation Failed', [
                'marketplace' => $listingInfo->store->storeType->name.' - '.$listingInfo->store->name,
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
//        $marketplaceService->getResultFeed($this->listingId);
    }
}
