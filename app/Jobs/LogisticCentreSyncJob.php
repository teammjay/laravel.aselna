<?php

namespace App\Jobs;

use App\Models\LogisticCenter;
use App\Models\StoreLogisticCenter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class LogisticCentreSyncJob implements ShouldQueue
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
        $logisticCentre = DB::connection('easyesale_db')->table('logistic_center')->cursor();
        foreach ($logisticCentre as $centre) {
            $this->createLogisticCentre($centre);
        }

        $storeLogisticCentre = DB::connection('easyesale_db')->table('store_logistic_center')->cursor();
        foreach ($storeLogisticCentre as $centre) {
            $this->createStoreLogisticCentre($centre);
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
