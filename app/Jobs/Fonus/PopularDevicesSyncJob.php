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
        $this->updateTrackingRecord(['tags' => 'Fonus Popular devices']);

        try {
            $popularDevices = DB::connection('fonus_db')->table('tbl_popular_devices')->get();
            foreach ($popularDevices as $device) {
                $this->createPopularDevice($device);
            }

            PopularDevice::orderBy('device_id')
                ->chunk(1000, function ($popularDevices) {
                    $this->deleteMissingPopularDevices($popularDevices);
                });

        }catch (\Throwable $e) {

            CriticalErrorService::report('Fonus Popular devices Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createPopularDevice(mixed $device)
    {
        $device = Device::where('code', $device->device_id)->first();
        $deviceType = DeviceTypes::where('code', $device->device_type_id)->first();
        if ($device && $deviceType) {
            PopularDevice::upsert([
                'device_id' => $device->id,
                'device_type_id' => $deviceType->id,
            ], [
                'device_id' => $device->id,
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
