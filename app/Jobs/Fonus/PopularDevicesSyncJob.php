<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceTypes;
use App\Models\Manufacturer;
use App\Models\PopularDevice;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopularDevicesSyncJob implements ShouldQueue
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
            'tags' => 'Fonus Popular devices'
        ]);

        try {
            $popularDevices = DB::connection('fonus_db')->table('tbl_popular_devices')->get();
            foreach ($popularDevices as $device) {
                $this->createPopularDevice($device);
            }

            PopularDevice::orderBy('device_id')
                ->chunk(1000, function ($popularDevices) {
                    $this->deleteMissingPopularDevices($popularDevices);
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

            CriticalErrorService::report('Fonus Popular devices Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createPopularDevice(mixed $device)
    {
        $aselnaDevice = Device::where('code', $device->device_id)->first();
        $deviceType = DeviceTypes::where('code', $device->device_type_id)->first();
        Log::info('Fonus Device: '.json_encode($device));
        Log::info('Popular device sync: device code - '.$aselnaDevice->code.', device type code - '.$aselnaDevice->code);
        if ($aselnaDevice && $deviceType) {
            PopularDevice::updateOrCreate([
                'device_id' => $aselnaDevice->id,
                'device_type_id' => $deviceType->id,
            ], [
                'device_id' => $aselnaDevice->id,
                'device_type_id' => $deviceType->id,
                'priority' => $device->priority,
            ]);
        }
    }

    private function deleteMissingPopularDevices($popularDevices)
    {
        $existingCodes = DB::connection('fonus_db')->table('tbl_popular_devices')
            ->pluck('device_id')
            ->toArray();

        foreach ($popularDevices as $popularDevice) {
            $device = Device::find($popularDevice->device_id);
            if ($device && !in_array($device->code, $existingCodes)) {
                $popularDevice->delete();
            }
        }
    }
}
