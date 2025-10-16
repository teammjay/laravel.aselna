<?php

namespace App\Jobs;

use App\Models\ListingItemProductImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ListingItemProductImageSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $listingItemLinksQ = DB::connection('easyesale_db')->table('listing_item_product_image')
            ->orderBy('id');

        if (env('APP_ENV') === 'local') {
            $listingItemLinksQ->whereRaw('listing_item_id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\')');
        }

        $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemLink($listingItemLink);
            }
        });
    }

    private function createListingItemLink(mixed $listingItemLink)
    {
        ListingItemProductImage::updateOrCreate([
            'id' => $listingItemLink->id
        ], [
            'id' => $listingItemLink->id,
            'listing_item_id' => $listingItemLink->listing_item_id,
            'product_image_id' => $listingItemLink->product_image_id,
            'image_file_system_id' => $listingItemLink->image_file_system_id,
            'path' => $listingItemLink->path,
            'reference_id' => $listingItemLink->reference_id
        ]);
    }
}
