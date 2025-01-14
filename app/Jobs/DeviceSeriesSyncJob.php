<?php

namespace App\Jobs;

use App\Models\DeviceSeries;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeviceSeriesSyncJob implements ShouldQueue
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
        DB::connection('easyesale_db')->table('cea_device_series')
            ->orderBy('id')
            ->chunk(1000, function ($syncDeviceSeries) {
                foreach ($syncDeviceSeries as $deviceSeries) {
                    $this->createSyncDeviceSeries($deviceSeries);
                }
            });
    }

    private function createSyncDeviceSeries(mixed $deviceSeries)
    {
        DeviceSeries::updateOrCreate(
            [
                'id' => $deviceSeries->id,
            ],
            [
                'id' => $deviceSeries->id,
                'name' => $deviceSeries->name,
                'slug' => $deviceSeries->id,
                'status' => 'active',
            ]
        );
    }
}
