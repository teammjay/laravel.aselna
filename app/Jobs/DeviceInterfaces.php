<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceInterfaces implements ShouldQueue
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
        $deviceAttributes = DB::connection('fonus_db')->table('tbl_device_attributes')->get();
        foreach ($deviceAttributes as $deviceAttribute) {
            $this->createDeviceInterfaces($deviceAttribute);
        }
    }

    private function createDeviceInterfaces(mixed $deviceAttribute)
    {
        $deviceId = Device::where('code', $deviceAttribute->device_id)->value('id');
        $interfaceId = Interfaces::where('code', $deviceAttribute->attribute_id)->value('id');
        if (DeviceInterface::where(['device_id' => $deviceId, 'interface_id' => $interfaceId])->exists()) {
            return;
        }
        DeviceInterface::create([
            'id' => (string) Str::uuid(),
            'device_id' => $deviceId,
            'interface_id' => $interfaceId,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
