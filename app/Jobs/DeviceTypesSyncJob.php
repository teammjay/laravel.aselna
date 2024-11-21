<?php

namespace App\Jobs;

use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $data = DB::connection('fonus_db')->table('tbl_device_type')->get();
        foreach ($data as $deviceType) {
            if (DeviceTypes::where(['code' => $deviceType->device_type_id])->exists()) {
//                Log::info('Device Type exists: ' . $deviceType->device_type_id);
                continue;
            }
            $this->createDeviceType($deviceType);
        }

        $deviceSubTypes = DB::connection('fonus_db')->table('tbl_device_type_sub')->get();
        foreach ($deviceSubTypes as $deviceSubType) {
            if (DeviceSubTypes::where(['code' => $deviceSubType->device_type_sub_id])->exists()) {
//                Log::info('Device Sub Type exists: ' . $deviceSubType->device_type_sub_id);
                continue;
            }
            $this->createDeviceSubType($deviceSubType);
        }
    }

    /**
     * @param mixed $deviceType
     * @return void
     */
    public function createDeviceType(mixed $deviceType): void
    {
//        Log::info('Device Type: ' . json_encode($deviceType));
        DeviceTypes::updateOrInsert(
            ['code' => $deviceType->device_type_id],
            [
                'id' => (string) Str::uuid(),
                'name' => $deviceType->device_type,
                'code' => $deviceType->device_type_id,
                'notes1' => $deviceType->notes1,
                'status' => 'active',
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    private function createDeviceSubType(mixed $deviceSubType)
    {
//        Log::info('Device Sub Type: ' . json_encode($deviceSubType));
        $deviceType = DeviceTypes::where('code', $deviceSubType->device_type_id)->first();
        DeviceSubTypes::updateOrInsert(
            ['code' => $deviceSubType->device_type_sub_id],
            [
                'id' => (string) Str::uuid(),
                'name' => $deviceSubType->device_sub_type,
                'code' => $deviceSubType->device_type_sub_id,
                'notes1' => $deviceSubType->notes1,
                'device_type_id' => $deviceType->id,
                'status' => 'active',
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }
}
