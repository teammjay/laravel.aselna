<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeviceTypesSyncJob implements ShouldQueue
{
    //use Dispatchable, InteractsWithQueue, SerializesModels;
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Device Type']);

        try {
            $deviceTypes = DB::connection('easyesale_db')->table('cea_device_type')->cursor();
            foreach ($deviceTypes as $deviceType) {
                $this->createSyncDeviceType($deviceType);
            }

            $deviceSubTypes = DB::connection('easyesale_db')->table('cea_device_sub_type')->cursor();
            foreach ($deviceSubTypes as $deviceSubType) {
                $this->createSyncDeviceSubType($deviceSubType);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Device Types Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }

//        $data = DB::connection('fonus_db')->table('tbl_device_type')->cursor();
//        foreach ($data as $deviceType) {
//            $this->createDeviceType($deviceType);
//        }
//
//        $deviceSubTypes = DB::connection('fonus_db')->table('tbl_device_type_sub')->cursor();
//        foreach ($deviceSubTypes as $deviceSubType) {
//            $this->createDeviceSubType($deviceSubType);
//        }
    }

    /**
     * @param mixed $deviceType
     * @return void
     */
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

    private function createSyncDeviceType(mixed $deviceType)
    {
        DeviceTypes::updateOrCreate([
            'id' => $deviceType->id,
        ], [
            'id' => $deviceType->id,
            'name' => $deviceType->name,
            'code' => $deviceType->code,
        ]);
    }

    private function createSyncDeviceSubType(mixed $deviceSubType)
    {
        DeviceSubTypes::updateOrCreate([
            'id' => $deviceSubType->id,
        ], [
            'id' => $deviceSubType->id,
            'name' => $deviceSubType->name,
            'code' => $deviceSubType->code,
            'device_type_id' => $deviceSubType->device_type_id,
        ]);
    }
}
