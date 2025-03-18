<?php

namespace App\Jobs;

use App\Models\DeviceCarrier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeviceCarrierSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('cea_device_carrier')->orderBy('id')
        ->chunk(100, function ($deviceCarriers) {
            foreach ($deviceCarriers as $deviceCarrier) {
                $this->createDeviceCarrier($deviceCarrier);
            }
        });
    }

    private function createDeviceCarrier(mixed $deviceCarrier)
    {
        DeviceCarrier::updateOrCreate(
            ['id' => $deviceCarrier->id],
            [
                'id' => $deviceCarrier->id,
                'device_id' => $deviceCarrier->device_id,
                'carrier_id' => $deviceCarrier->carrier_id,
            ]
        );
    }
}
