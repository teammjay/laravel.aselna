<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DeviceInterfaces implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 300000;

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
        $this->updateTrackingRecord(['tags' => 'Device Interface']);

        DB::connection('easyesale_db')->table('cea_device_interface')->orderBy('id')
            ->chunk(1000, function ($deviceInterfaces) {
                foreach ($deviceInterfaces as $deviceInterface) {
                    $this->createDeviceInetrface($deviceInterface);
                }
            });

        DeviceInterface::orderBy('id')->chunk(1000, function ($deviceInterfaces) {
            $this->checkAndDelete($deviceInterfaces);
        });

//        $deviceAttributes = DB::connection('fonus_db')->table('tbl_device_attributes')->cursor();
//        foreach ($deviceAttributes as $deviceAttribute) {
////            $this->createDeviceInterfaces($deviceAttribute);
//        }
    }

    private function checkAndDelete(mixed $deviceInterfaces)
    {
        $deviceIds = [];
        $interfaceMap = [];

        foreach ($deviceInterfaces as $deviceInterface) {
            $key = $deviceInterface->device_id . '_' . $deviceInterface->interface_id;
            $deviceIds[] = $deviceInterface->device_id;
            $interfaceMap[$key] = $deviceInterface->id;
        }

        $existingRecords = DB::connection('easyesale_db')->table('cea_device_interface')
            ->whereIn('device_id', array_unique($deviceIds))
            ->select('device_id', 'cea_interface_id')
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->device_id . '_' . $record->cea_interface_id] = true;
        }

        $idsToDelete = [];
        foreach ($interfaceMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
            }
        }

        // Perform batch deletion if needed
        if (!empty($idsToDelete)) {
            DeviceInterface::whereIn('id', $idsToDelete)->delete();
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
            'id' => (string)Str::uuid(),
            'device_id' => $deviceId,
            'interface_id' => $interfaceId,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDeviceInetrface(mixed $deviceInterface)
    {
        Log::info('Syncing device interface: ' . $deviceInterface->id);
        if (Device::where('id', $deviceInterface->device_id)->doesntExist()) {
            return;
        }
        if (Interfaces::where('id', $deviceInterface->cea_interface_id)->doesntExist()) {
            return;
        }
        DeviceInterface::updateOrCreate([
            'id' => $deviceInterface->id
        ], [
            'id' => $deviceInterface->id,
            'device_id' => $deviceInterface->device_id,
            'interface_id' => $deviceInterface->cea_interface_id,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
