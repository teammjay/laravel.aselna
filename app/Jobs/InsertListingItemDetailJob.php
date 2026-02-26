<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Services\CriticalErrorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class InsertListingItemDetailJob implements ShouldQueue
{
    use Dispatchable, Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable, Dispatchable;
        Queueable::withChain as queueableWithChain;
        Dispatchable::withChain as dispatchableWithChain;
    }
    //use Dispatchable, Queueable, InteractsWithQueue, SerializesModels, Batchable;

    public $chunk;
    public $store_type;
    public $store_name;

    /**
     * Create a new job instance.
     */
    public function __construct(array $chunk, $storeDetail)
    {
        $this->chunk = $chunk;
        $this->store_type = $storeDetail->storeType->name ?? "";
        $this->store_name = $storeDetail->name;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => $this->store_name . ' ' . $this->store_type . ' Listing Item Detail']);
        try {
            DB::disableQueryLog(); // reduce memory
            Log::info('Item detail chunk execute');
            DB::table('listing_item_detail')->insert($this->chunk);
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
            CriticalErrorService::report('Insert Listing Item Detail Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }
}
