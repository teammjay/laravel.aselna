<?php

namespace App\Jobs\Oydera;

use App\Models\Manufacturer;
use App\Services\CriticalErrorService;
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Manufaturer']);

        try {
            $manufaturer = DB::connection('easyesale_db')->table('manufacturer')->cursor();

            foreach ($manufaturer as $manufacture) {
                $this->createManufacturer($manufacture);
            }

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

            CriticalErrorService::report('Manufacturer Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
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
