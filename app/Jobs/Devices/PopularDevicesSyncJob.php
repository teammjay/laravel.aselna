<?php

namespace App\Jobs\Devices;

use App\Jobs\Concerns\TrackableJob;
use App\Models\PopularDevice;
use Exception;
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

    public $timeout = 30000;

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
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Popular Devices']);

            DB::connection('easyesale_db')->table('cea_popular_device')->orderBy('id')
            ->chunk(1000, function ($popularDevices) {;
                    foreach ($popularDevices as $popularDevice) {
                        $this->createSyncPopularDevice($popularDevice);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Popular Devices Sync Job Failed: ' . $e->getMessage());
        }
    }

    private function createSyncPopularDevice(mixed $popularDevice)
    {
        Log::info("Syncing Popular Device ID: {$popularDevice->id}");
        PopularDevice::updateOrCreate(
            [
                'id' => $popularDevice->id,
            ],
            [
                'id' => $popularDevice->id,
                'device_type_id' => $popularDevice->device_type_id,
                'device_id' => $popularDevice->device_id,
                'priority' => $popularDevice->priority,
            ]
        );
    }
}
