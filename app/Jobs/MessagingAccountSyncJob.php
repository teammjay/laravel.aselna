<?php

namespace App\Jobs;

use App\Models\MessagingAccount;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class MessagingAccountSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Messaging Account']);

        try {
            DB::connection('easyesale_db')->table('messaging_account')->orderBy('id')
                ->chunk(100, function ($messagingAccounts) {
                    foreach ($messagingAccounts as $messagingAccount) {
                        $this->createSyncMessagingAccount($messagingAccount);
                    }
                });

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

            CriticalErrorService::report('Messaging Account Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncMessagingAccount(mixed $messagingAccount)
    {
        $created_by = auth()->user()->name ?? 'admin';
        MessagingAccount::updateOrCreate(
            ['id' => $messagingAccount->id],
            [
                'id' => $messagingAccount->id,
                'protocol' => $messagingAccount->protocol,
                'email' => $messagingAccount->email,
                'user_id' => $messagingAccount->user_id,
                'password' => $messagingAccount->password,
                'server' => $messagingAccount->server,
                'port' => $messagingAccount->port,
                'requires_ssl' => $messagingAccount->requires_ssl,
                'requires_tls' => $messagingAccount->requires_tls,
                'requires_authentication' => $messagingAccount->requires_authentication,
                'ssl_port' => $messagingAccount->ssl_port,
                'tls_port' => $messagingAccount->tls_port,
                'filter_from_date' => $messagingAccount->filter_from_date,
                'folders' => $messagingAccount->folders,
                'last_sync_date' => $messagingAccount->last_sync_date,
                'created_by' => $created_by,
            ]
        );
    }
}
