<?php

namespace App\Jobs;

use App\Constants\ListingStatus;
use App\Models\ListingItem;
use App\Models\ListingItemDetail;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ListingItemDetailStatusCheckJob implements ShouldQueue
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

        try {

            $this->updateTrackingRecord(['tags' => 'Listing Item Status Check']);

            ListingItemDetail::whereHas('listingItem', function ($query) {
                $query->where('status', ListingStatus::Deleted);
            })->chunk(100, function ($itemDetails) {
                foreach ($itemDetails as $detail) {
                    Log::info('Item Detail ID :'.$detail->id);
                    $detail->update(['kicked' => 1]);
                }
            });
        }catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Listing Item Detail Status Check Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error("Error Listing Item Detail Status Check : " . $e->getMessage());
        }
    }

}
