<?php

namespace App\Jobs;

use App\Models\PopularDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class PopularDeviceSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'Popular Device']);
        DB::connection('easyesale_db')->table('cea_popular_device')->orderBy('id')
            ->chunk(1000, function ($popularDevices) {
                foreach ($popularDevices as $popularDevice) {
                    $this->createSyncPopularDevice($popularDevice);
                }
            });
    }

    private function createSyncPopularDevice(mixed $popularDevice)
    {
        PopularDevice::updateOrCreate(
            ['id' => $popularDevice->id],
            [
                'id' => $popularDevice->id,
                'device_type_id' => $popularDevice->device_type_id,
                'device_id' => $popularDevice->device_id,
            ]
        );
    }
}