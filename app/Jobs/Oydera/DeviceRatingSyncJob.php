<?php

namespace App\Jobs\Oydera;

use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceRating;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeviceRatingSyncJob implements ShouldQueue
{
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Device Rating']);

        try {
            DB::connection('easyesale_db')
                ->table('cea_device_rating')
                ->orderBy('id')
                ->chunk(1000, function ($deviceRatings) {
                    foreach ($deviceRatings as $deviceRating) {
                        $this->syncDeviceRating($deviceRating);
                    }
                });


            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Device Rating Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

        }
    }

    private function syncDeviceRating(mixed $deviceRating)
    {
        DeviceRating::updateOrCreate(
            ['id' => $deviceRating->id],
            [
                'id' => $deviceRating->id,
                'value' => $deviceRating->value,
                'status' => true
            ]
        );
    }
}
