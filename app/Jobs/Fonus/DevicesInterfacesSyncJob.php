<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DevicesInterfacesSyncJob implements ShouldQueue
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
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Device Interface']);

        try {
            $deviceAttributes = DB::connection('fonus_db')->table('tbl_device_attributes')->get();
            foreach ($deviceAttributes as $attribute) {
                $this->createDeviceInterfaces($attribute);
            }

            $deviceAttributes = DB::connection('fonus_db')->table('tbl_gadget_attributes')->get();
            foreach ($deviceAttributes as $attribute) {
                $this->createGadgetInterfaces($attribute);
            }

            DeviceInterface::orderBy('id')->chunk(1000, function ($deviceInterfaces) {
                $this->checkAndDelete($deviceInterfaces);
            });

        } catch (\Throwable $e) {
            CriticalErrorService::report('Fonus Device Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function checkAndDelete(mixed $deviceInterfaces)
    {
        $deviceKeys = [];
        $interfaceMap = [];
        $interfaceDeviceID = [];

        foreach ($deviceInterfaces as $deviceInterface) {
            $deviceId = $deviceInterface->device->code;
            $interfaceId = $deviceInterface->interfaces->code;
            $key = $deviceId . '_' . $interfaceId;
            $deviceKeys[] = $key;
            $interfaceMap[$key] = $deviceInterface->id;
            $interfaceDeviceID[$key] = $deviceInterface->device_id;
        }

//        Log::info('Device :: ' . json_encode($deviceKeys));

        $existingRecords = DB::connection('fonus_db')->table('tbl_device_attributes')
            ->select(DB::raw("CONCAT(device_id, '_', attribute_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(device_id, '_', attribute_id)"), array_unique($deviceKeys))
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->key] = true;
        }

        $existingGadgetRecords = DB::connection('fonus_db')->table('tbl_gadget_attributes')
            ->select(DB::raw("CONCAT(gadget_id, '_', attribute_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(gadget_id, '_', attribute_id)"), array_unique($deviceKeys))
            ->get();

        foreach ($existingGadgetRecords as $record) {
            $existingSet[$record->key] = true;
        }


        $idsToDelete = [];
        foreach ($interfaceMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
                Device::where('id', $interfaceDeviceID[$key])->update(['updated_at' => now()]);
            }
        }
        Log::info('Device Interfaces to delete: ' . implode(',', $idsToDelete));

        // Perform batch deletion if needed
        if (!empty($idsToDelete)) {
            DeviceInterface::whereIn('id', $idsToDelete)->delete();
        }
    }

    private function createDeviceInterfaces(mixed $deviceAttribute)
    {
        $deviceId = Device::where('code', $deviceAttribute->device_id)->value('id');
        $interfaceId = Interfaces::where('code', $deviceAttribute->attribute_id)->value('id');
        if (!isset($deviceId) || !isset($interfaceId)) {
            return;
        }
        if (DeviceInterface::where(['device_id' => $deviceId, 'interface_id' => $interfaceId])->exists()) {
            return;
        }
        DeviceInterface::create([
            'device_id' => $deviceId,
            'interface_id' => $interfaceId,
            'created_by' => 'system',
            'updated_by' => 'system',
        ]);
    }
    private function createGadgetInterfaces(mixed $deviceAttribute)
    {
        $deviceId = Device::where('code', $deviceAttribute->gadget_id)->value('id');
        $interfaceId = Interfaces::where('code', $deviceAttribute->attribute_id)->value('id');
        if (!isset($deviceId) || !isset($interfaceId)) {
            return;
        }
        if (DeviceInterface::where(['device_id' => $deviceId, 'interface_id' => $interfaceId])->exists()) {
            return;
        }
        Log::info('Creating Gadget Interface: Device ID ' . $deviceId . ' Interface ID ' . $interfaceId);
        DeviceInterface::create([
            'device_id' => $deviceId,
            'interface_id' => $interfaceId,
            'created_by' => 'system',
            'updated_by' => 'system',
        ]);
    }
}
