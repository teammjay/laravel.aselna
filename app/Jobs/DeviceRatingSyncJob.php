<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\DeviceRating;
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
        DB::connection('easyesale_db')
            ->table('cea_device_rating')
            ->orderBy('id')
            ->chunk(1000, function ($deviceRatings) {
                foreach ($deviceRatings as $deviceRating) {
                    $this->syncDeviceRating($deviceRating);
                }
            });
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
