<?php

namespace App\Jobs;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class InsertListingItemDetailJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $chunk;

    /**
     * Create a new job instance.
     */
    public function __construct(array $chunk)
    {
        $this->chunk = $chunk;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::disableQueryLog(); // reduce memory
        Log::info('Item detail chunk execute');
        DB::table('listing_item_detail')->insert($this->chunk);
    }
}
