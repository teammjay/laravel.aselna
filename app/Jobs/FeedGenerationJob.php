<?php

namespace App\Jobs;

use App\Models\Listing;
use App\Services\Marketplace\MarketplaceServiceFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FeedGenerationJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

    private mixed $listingId;

    /**
     * Create a new job instance.
     */
    public function __construct($ListingId)
    {
        $this->listingId = $ListingId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $listingFeed = Listing::where('id', $this->listingId)->first();
        Log::info('Listing Feed: ' . $listingFeed);

        // get StoreType froms store that is inside listingfeed
        $storeType = $listingFeed->store->storeType->name;
        Log::info('Store Type: ' . $storeType);

        $marketplaceService = MarketplaceServiceFactory::create($storeType);
        Log::info('Marketplace Service Created');

        $marketplaceService->generateFeed($this->listingId);
        Log::info('Feed Generated');

//        $marketplaceService->submitFeed('',$this->listingId);

//        $marketplaceService->getResultFeed($this->listingId);

    }
}
