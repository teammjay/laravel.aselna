<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Models\ListingFeed;
use App\Models\ListingItemLink;
use App\Services\Marketplace\MarketplaceServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FeedGenerationJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

    private mixed $listingId;

    private mixed $feedId;

    /**
     * Create a new job instance.
     */
    public function __construct($ListingId,$feedId = null)
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

        // get StoreType froms store that is inside listingfeed
        $storeType = $listingInfo->store->storeType->name;
        Log::info('Store Type: ' . $storeType);

        $marketplaceService = MarketplaceServiceFactory::create($storeType);
        Log::info('Marketplace Service Created');

        Log::info('Generating Feed Starts');
        $created_by = auth()->user()->name ?? 'admin';
        $feedData = array(
            'listing_id' => $this->listingId,
            'status' => 0,
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

        $marketplaceService->generateFeed($this->listingId,$feedInfo->id);
        Log::info('Feed Generated');
        $itemCount = ListingItemLink::where('listing_id', $this->listingId)->count();

        ListingFeed::where(['listing_id' => $this->listingId])->update(['number_of_items' => $itemCount]);

//        $marketplaceService->submitFeed('',$this->listingId);
//
//        $marketplaceService->getResultFeed($this->listingId);


    }
}
