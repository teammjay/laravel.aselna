<?php

namespace App\Jobs;

use App\Models\DeviceSeries;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeviceSeriesSyncJob implements ShouldQueue
{
    use Queueable;

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
        Log::info('Device Series Sync Job Start');
        DB::connection('easyesale_db')->table('cea_device_series')
            ->orderBy('id')
            ->chunk(1000, function ($syncDeviceSeries) {
                foreach ($syncDeviceSeries as $deviceSeries) {
                    $this->createSyncDeviceSeries($deviceSeries);
                }
            });
        Log::info('Device Series Sync Job End');

        Log::info('Device Series Delete Job Start');
        DeviceSeries::orderBy('id')->chunk(1000, function ($deviceSeries) {
            Log::info('Processing chunk of ' . count($deviceSeries) . ' device series for deletion check.');
            $this->checkAndDelete($deviceSeries);
        });
        Log::info('Device Series Delete Job End');
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

    private function checkAndDelete($deviceSeries)
    {
        $deviceSeriesIds = [];
        $seriesMap = [];

        foreach ($deviceSeries as $series) {
            $seriesMap[$series->id] = $series->id;
            $deviceSeriesIds[] = $series->id;
        }

        $existingRecords = DB::connection('easyesale_db')->table('cea_device_series')
            ->whereIn('id', array_unique($deviceSeriesIds))
            ->select('id')
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->id] = true;
        }

        $idsToDelete = [];
        foreach ($seriesMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
            }
        }

        // Perform batch deletion if needed
        if (!empty($idsToDelete)) {
            Log::info('Deleting Device Series IDs: ' . implode(',', $idsToDelete));
            DeviceSeries::whereIn('id', $idsToDelete)->delete();
        }
    }
}
