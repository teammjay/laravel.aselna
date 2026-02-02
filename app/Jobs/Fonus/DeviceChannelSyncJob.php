<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\DeviceStoreTypes;
use App\Models\StoreType;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceChannelSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Fonus Device Channel']);

        try {
            $deviceChannels = DB::connection('fonus_db')->table('tbl_device_channels')->get();
            foreach ($deviceChannels as $deviceChannel) {
                $this->createOrUpdateDeviceChannel($deviceChannel);
            }
        }catch (\Throwable $e) {
            Log::error('Fonus Product Channel Sync Job Failed: ' . $e->getTraceAsString());

            CriticalErrorService::report('Product Interface Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createOrUpdateDeviceChannel(mixed $deviceChannel)
    {
        $device = Device::where('code', $deviceChannel->device_id)->first();
        $storeType = StoreType::where('code', $deviceChannel->channel)->first();
        if (isset($device) && isset($storeType)) {
            $existingRecord = DeviceStoreTypes::where('device_id', $device->id)
                ->where('store_type_id', $storeType->id)
                ->first();
            if ($existingRecord) {
                $existingRecord->update([
                    'status' => $deviceChannel->list,
                ]);
            } else {
                DeviceStoreTypes::create([
                    'device_id' => $device->id,
                    'store_type_id' => $storeType->id,
                    'status' => $deviceChannel->list
                ]);
            }
        }
    }
}
