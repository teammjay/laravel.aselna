<?php

namespace App\Jobs;

use App\Models\ListingItemProductImage;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;
use Illuminate\Support\Facades\Log;

class ListingItemProductImageSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 600000;

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
//        $this->ListingItemImagesAll();

            $this->listingItemImagesByStore('6c7009ce-eac1-a995-a986-27d5df0103ac');
        }catch (\Throwable $e) {

            CriticalErrorService::report('Listing Item Product Images Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function createListingItemImage(mixed $listingItemLink)
    {
        Log::info('Syncing Listing Item Product Image: ' . $listingItemLink->id);
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

    /**
     * @return void
     */
    public function ListingItemImagesAll(): void
    {
        $this->updateTrackingRecord(['tags' => 'Listing Item Product Image']);
        $listingItemLinksQ = DB::connection('easyesale_db')->table('listing_item_product_image')
            ->orderBy('id');

        if (env('APP_ENV') === 'local') {
            $listingItemLinksQ->whereRaw('listing_item_id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\')');
        }

        $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemImage($listingItemLink);
            }
        });
    }

    private function listingItemImagesByStore(string $storeId)
    {
        $this->updateTrackingRecord(['tags' => 'Listing Item Product Image By Store']);
        $listingItemLinksQ = DB::connection('easyesale_db')->table('listing_item_product_image')
            ->whereIn('listing_item_id', function ($query) use ($storeId) {
                $query->select('listing_item.id')
                    ->from('listing_item')
                    ->join('store_product', 'listing_item.store_product_id', '=', 'store_product.id')
                    ->where('store_product.store_id', $storeId);
            })
            ->orderBy('id');

        if (env('APP_ENV') === 'local') {
//            $listingItemLinksQ->whereRaw('listing_item_id in (select item_id from oydera_listing_item_link where listing_id = \'1dd2cd15-757d-d9fc-02af-21a1eba741e7\')');
        }

        $listingItemLinksQ->chunk(1000, function ($listingItemLinks) {
            foreach ($listingItemLinks as $listingItemLink) {
                $this->createListingItemImage($listingItemLink);
            }
        });
    }
}
