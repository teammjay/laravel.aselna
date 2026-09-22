<?php

namespace App\Jobs\Oydera;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Carrier;
use App\Models\Device;
use App\Models\DeviceCarrier;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeviceCarrierSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Device Carrier']);

        try {
            DB::connection('easyesale_db')->table('cea_device_carrier')->orderBy('id')
                ->chunk(100, function ($deviceCarriers) {
                    foreach ($deviceCarriers as $deviceCarrier) {
                        $this->createDeviceCarrier($deviceCarrier);
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

            CriticalErrorService::report('Device Carrier Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createDeviceCarrier(mixed $deviceCarrier)
    {
        if (Device::where('id', $deviceCarrier->device_id)->doesntExist()) {
            return;
        }
        if (Carrier::where('id', $deviceCarrier->carrier_id)->doesntExist()) {
            return;
        }
        DeviceCarrier::updateOrCreate(
            [
                'id' => $deviceCarrier->id,
                'device_id' => $deviceCarrier->device_id,
                'carrier_id' => $deviceCarrier->carrier_id,
            ],
            [
                'id' => $deviceCarrier->id,
                'device_id' => $deviceCarrier->device_id,
                'carrier_id' => $deviceCarrier->carrier_id,
            ]
        );
    }
}
