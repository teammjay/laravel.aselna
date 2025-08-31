<?php

namespace App\Jobs;

use App\Mail\PendingTrackingNumbersMail;
use App\Models\AccountTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNotificationForPendingTransactionsJob implements ShouldQueue
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
        $tracking_numbers = AccountTransaction::select('tracking_number')->with('shippingAccount')->where('status', 'pending')
            ->where('transaction_date', '<=', Carbon::now()->subDays(10))
            ->orderBy('transaction_date','asc')
            ->pluck('tracking_number');

        if ($tracking_numbers->count() > 0) {
            Mail::to('jayesh@mjailab.com')
                ->send(new PendingTrackingNumbersMail($tracking_numbers));
        }

    }

}
