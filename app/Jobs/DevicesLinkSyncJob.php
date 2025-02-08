<?php

namespace App\Jobs;

use App\Models\DevicesLinkedDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DevicesLinkSyncJob implements ShouldQueue
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
        $syncDevices = DB::connection('easyesale_db')->table('cea_device_cea_device_series_link')->get();

        foreach ($syncDevices as $device) {
            $this->createSyncDevice($device);
        }
    }

    private function createSyncDevice(mixed $device)
    {
        DevicesLinkedDevice::updateOrCreate([
            'device_id' => $device->cea_device_id,
            'device_series_id' => $device->cea_device_series_id,
        ], [
            'device_id' => $device->cea_device_id,
            'device_series_id' => $device->cea_device_series_id,
        ]);
    }
}
