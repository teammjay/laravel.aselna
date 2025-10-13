<?php

namespace App\Jobs;

use App\Constants\ListingStatus;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

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

        try {
            ListingItemDetail::whereHas('listingItem', function ($query) {
                $query->where('status', ListingStatus::Deleted);
            })->chunk(100, function ($itemDetails) {
                foreach ($itemDetails as $detail) {
                    Log::info('Item Detail ID :'.$detail->id);
                    $detail->update(['kicked' => 1]);
                }
            });
        }catch (\Exception $e) {
                Log::error("Error Listing Item Detail Status Check : " . $e->getMessage());
        }
    }

}
