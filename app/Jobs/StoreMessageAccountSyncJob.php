<?php

namespace App\Jobs;

use App\Models\StoreMessageAccount;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class StoreMessageAccountSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Store Message Accounts']);

        try {
            DB::connection('easyesale_db')->table('store_message_account')->orderBy('id')
                ->chunk(100, function ($storeMessageAccounts) {
                    foreach ($storeMessageAccounts as $storeMessageAccount) {
                        $this->createSyncStoreMessageAccount($storeMessageAccount);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Store Message Accounts Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncStoreMessageAccount(mixed $storeMessageAccount)
    {
        $created_by = auth()->user()->name ?? 'admin';

        StoreMessageAccount::updateOrCreate(
            ['id' => $storeMessageAccount->id],
            [
                'id' => $storeMessageAccount->id,
                'store_id' => $storeMessageAccount->store_id,
                'name' => $storeMessageAccount->name,
                'email' => $storeMessageAccount->email,
                'client_id' => $storeMessageAccount->client,
                'incoming_account_id' => $storeMessageAccount->incoming_account_id,
                'outgoing_account_id' => $storeMessageAccount->outgoing_account_id,
                'api_account' => $storeMessageAccount->api_account,
                'active' => $storeMessageAccount->active,
                'version' => $storeMessageAccount->version,
                'created_by' => $created_by,
                'updated_by' => $created_by,
            ]
        );
    }
}
