<?php

namespace App\Jobs;

use App\Models\StoreMessageAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailSyncFailJob implements ShouldQueue
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
        $thresholdMinutes = 300;

        $storeMessageAccounts = StoreMessageAccount::with('store','store.storeType','incomingAccount')
            ->where('active', 1)
            ->where('api_account', 0)
            ->get();

        foreach ($storeMessageAccounts as $macc) {
            if (isset($macc->incomingAccount->filter_from_date) && $macc->incomingAccount->filter_from_date != '') {

                $lastSync = \Carbon\Carbon::parse($macc->incomingAccount->filter_from_date);
                if ($lastSync->lt(now()->subMinutes($thresholdMinutes))) {
                    Log::info('Filter date: ' . $macc->incomingAccount->filter_from_date);
                    // send notification email
                    Mail::raw("Email Sync issue detected for store: {$macc->store->name} - {$macc->store->storeType->name}. \n Last sync at {$lastSync->toDateTimeString()}",
                        function ($message) use ($macc) {
                            $message->to('jayesh@mjailab.com')
                                ->subject("Store Email Sync Issue Alert : {$macc->store->name} - {$macc->store->storeType->name}");
                        }
                    );
                }
            }
        }
    }
}
