<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Client;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ClientSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Client']);

        try {
            DB::connection('easyesale_db')->table('client')
                ->orderBy('create_ts', 'desc')
                ->chunk(100, function ($clients) {
                    foreach ($clients as $client) {
                        $this->createSyncClient($client);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Client Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function createSyncClient(mixed $client)
    {
        if (!isset($client->name)) {
            return;
        }
        Client::updateOrCreate(
            [
                'id' => $client->id
            ],
            [
                'id' => $client->id,
                'name' => $client->name,
                'simple_product_code_generation_template' => $client->simple_product_code_generation_template,
            ]
        );
    }
}
