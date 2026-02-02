<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeviceTypeSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 5000;

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
        $this->updateTrackingRecord(['tags' => 'Fonus DeviceType Sync job']);

        try {
            $data = DB::connection('fonus_db')->table('tbl_device_type')->cursor();
            foreach ($data as $deviceType) {
                $this->createDeviceType($deviceType);
            }

            $deviceSubTypes = DB::connection('fonus_db')->table('tbl_device_type_sub')->cursor();
            foreach ($deviceSubTypes as $deviceSubType) {
                $this->createDeviceSubType($deviceSubType);
            }

            DeviceTypes::orderBy('code')
                ->chunk(1000, function ($deviceTypes) {
                    $this->deleteMissingDeviceTypes($deviceTypes);
                });

            DeviceSubTypes::orderBy('code')
                ->chunk(1000, function ($deviceSubTypes) {
                    $this->deleteMissingDeviceSubTypes($deviceSubTypes);
                });
        } catch (\Throwable $e) {

            CriticalErrorService::report('Fonus Device type Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    public function createDeviceType(mixed $deviceType): void
    {
        DeviceTypes::updateOrInsert(
            ['code' => $deviceType->device_type_id],
            [
                'name' => $deviceType->device_type,
                'notes1' => $deviceType->notes1,
            ]
        );
    }

    private function createDeviceSubType(mixed $deviceSubType)
    {
        $deviceType = DeviceTypes::where('code', $deviceSubType->device_type_id)->first();
        DeviceSubTypes::updateOrInsert(
            ['code' => $deviceSubType->device_type_sub_id],
            [
                'name' => $deviceSubType->device_sub_type,
                'code' => $deviceSubType->device_type_sub_id,
                'notes1' => $deviceSubType->notes1,
                'device_type_id' => (string) $deviceType->id,
                'status' => 'active',
            ]
        );
    }

    private function deleteMissingDeviceTypes($deviceTypes)
    {
        $existingCodes = DB::connection('fonus_db')->table('tbl_device_type')
            ->pluck('device_type_id')
            ->toArray();

        foreach ($deviceTypes as $deviceType) {
            if (!in_array($deviceType->code, $existingCodes)) {
                DeviceTypes::where('code', $deviceType->code)->delete();
            }
        }
    }

    private function deleteMissingDeviceSubTypes($deviceSubTypes)
    {
        $existingCodes = DB::connection('fonus_db')->table('tbl_device_type_sub')
            ->pluck('device_type_sub_id')
            ->toArray();

        foreach ($deviceSubTypes as $deviceSubType) {
            if (!in_array($deviceSubType->code, $existingCodes)) {
                DeviceSubTypes::where('code', $deviceSubType->code)->delete();
            }
        }
    }
}
