<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Carrier;
use App\Models\Device;
use App\Models\DeviceCarrier;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Device Carrier'
        ]);

        try {
            DB::connection('fonus_db')->table('tbl_device_carriers')->orderBy('device_id')->orderBy('carrier_id')
                ->chunk(100, function ($deviceCarriers) {
                    foreach ($deviceCarriers as $deviceCarrier) {
                        $this->createDeviceCarrier($deviceCarrier);
                    }
                });

            DeviceCarrier::orderBy('id')->chunk(1000, function ($deviceCarriers) {
                $this->checkAndDelete($deviceCarriers);
            });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            // Mark as failed when job fails
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

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createDeviceCarrier(mixed $deviceCarrier)
    {
        $deviceID = Device::where('code', $deviceCarrier->device_id)->value('id');
        $carrierID = Carrier::where('code', $deviceCarrier->carrier_id)->value('id');
        if (!$deviceID || !$carrierID) {
            return;
        }
        DeviceCarrier::updateOrCreate(
            [
                'device_id' => $deviceID,
                'carrier_id' => $carrierID,
            ],
            [
                'device_id' => $deviceID,
                'carrier_id' => $carrierID,
                'carrier_model_number' => $deviceCarrier->carrier_model_number,
            ]
        );
    }

    private function checkAndDelete($deviceCarriers)
    {
        $deviceCarrierKeys = [];
        $deviceCarrierMap = [];

        foreach ($deviceCarriers as $deviceCarrier) {
            $deviceId = $deviceCarrier->device->code;
            $carrierId = $deviceCarrier->carrier->code;
            $key = $deviceId . '_' . $carrierId;
            $deviceCarrierKeys[] = $key;
            $deviceCarrierMap[$key] = $deviceCarrier->id;
        }

        $existingRecords = DB::connection('fonus_db')->table('tbl_device_carriers')
            ->select(DB::raw("CONCAT(device_id, '_', carrier_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(device_id, '_', carrier_id)"), array_unique($deviceCarrierKeys))
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->key] = true;
        }

        $deleteIds = [];

        foreach ($deviceCarrierKeys as $key) {
            if (!isset($existingSet[$key])) {
                $deleteIds[] = $deviceCarrierMap[$key];
            }
        }

        Log::info('Device Carriers to delete: ' . implode(',', $deleteIds));
        if (!empty($deleteIds)) {
            DeviceCarrier::whereIn('id', $deleteIds)->delete();
        }
    }
}
