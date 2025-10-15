<?php

namespace App\Jobs;

use App\Models\Manufacturer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Jobs\Concerns\TrackableJob;

class ManufacturerSyncJob implements ShouldQueue
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
        $manufaturer = DB::connection('easyesale_db')->table('manufacturer')->cursor();

        foreach ($manufaturer as $manufacture) {
            $this->createManufacturer($manufacture);
        }
    }

    private function createManufacturer(mixed $manufacture)
    {
        Manufacturer::updateOrCreate(
            ['id' => $manufacture->id],
            [
                'id' => $manufacture->id,
                'name' => $manufacture->name,
                'code' => $manufacture->code,
                'notes' => $manufacture->notes,
                'client_id' => '',
                'sort_order' => $manufacture->sort_order,
                'version' => $manufacture->version,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
