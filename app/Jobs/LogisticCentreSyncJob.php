<?php

namespace App\Jobs;

use App\Models\LogisticCenter;
use App\Models\StoreLogisticCenter;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class LogisticCentreSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Logistic Centers']);

        try {
            $logisticCentre = DB::connection('easyesale_db')->table('logistic_center')->cursor();
            foreach ($logisticCentre as $centre) {
                $this->createLogisticCentre($centre);
            }

            $storeLogisticCentre = DB::connection('easyesale_db')->table('store_logistic_center')->cursor();
            foreach ($storeLogisticCentre as $centre) {
                $this->createStoreLogisticCentre($centre);
            }

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

            CriticalErrorService::report('Logistic Center Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createLogisticCentre($centre)
    {
        LogisticCenter::updateOrCreate([
            'id' => $centre->id
        ], [
            'id' => $centre->id,
            'name' => $centre->name,
            'client_id' => $centre->client,
            'center_type' => $centre->center_type,
            'from_address_id' => $centre->from_address_id,
            'return_address_id' => $centre->return_address_id,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createStoreLogisticCentre($centre)
    {
        StoreLogisticCenter::updateOrCreate([
            'id' => $centre->id
        ], [
            'id' => $centre->id,
            'store_id' => $centre->store_id,
            'logistic_center_id' => $centre->logistic_center_id,
            'overwrite_from_address' => $centre->overwrite_from_address,
            'overwrite_return_address' => $centre->overwrite_return_address,
            'from_address_id' => $centre->from_address_id,
            'return_address_id' => $centre->return_address_id,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
