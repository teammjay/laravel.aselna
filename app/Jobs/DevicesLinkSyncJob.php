<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\DevicesLinkedDevice;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DevicesLinkSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Device Link']);

        try {
            $syncDevices = DB::connection('easyesale_db')->table('cea_device_cea_device_series_link')->get();

            foreach ($syncDevices as $device) {
                $this->createSyncDevice($device);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Device Link Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
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
