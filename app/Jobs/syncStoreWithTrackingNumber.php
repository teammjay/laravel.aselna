<?php

namespace App\Jobs;

use App\Models\AccountTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class syncStoreWithTrackingNumber implements ShouldQueue
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
        DB::statement("
            UPDATE shipping_account_transaction AS at
            JOIN order_operation AS oo
                ON at.tracking_number = oo.tracking_number
            SET at.store_id = oo.store_id
            WHERE at.store_id IS NULL OR at.store_id = ''
        ");
    }
}
