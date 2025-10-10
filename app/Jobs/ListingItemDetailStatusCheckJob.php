<?php

namespace App\Jobs;

use App\Constants\ListingStatus;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ListingItemDetailStatusCheckJob implements ShouldQueue
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

        ListingItemDetail::whereHas('listingItem', function ($query) {
            $query->where('status', ListingStatus::Deleted);
        })->chunk(100, function ($itemDetails) {
            foreach ($itemDetails as $detail) {
                $detail->update(['kicked' => 1]);
            }
        });
    }

}
