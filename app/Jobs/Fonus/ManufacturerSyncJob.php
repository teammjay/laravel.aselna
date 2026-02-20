<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\Manufacturer;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Manufacturer'
        ]);

        try {
            $manufacturer = DB::connection('fonus_db')->table('tbl_manufacturer')->cursor();

            foreach ($manufacturer as $manufacture) {
                $this->createManufacturer($manufacture);
            }

            Manufacturer::orderBy('code')
                ->chunk(100, function ($manufacturers) {
                    $this->checkAnddelete($manufacturers);
                });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Manufacturer Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createManufacturer(mixed $manufacture)
    {
        if (env('APP_ENV') !== 'local') {
            Log::info('Syncing Images Also');
            try {
                //ensure directory exists
                if (!file_exists(storage_path('app/public/manufacturer_images/'))) {
                    mkdir(storage_path('app/public/manufacturer_images/'), 0777, true);
                }
                if (!file_exists(storage_path('app/public/manufacturer_images/' . $manufacture->picture1))) {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/manufacturer_images/' . $manufacture->picture1);
                    file_put_contents(storage_path('app/public/manufacturer_images/' . $manufacture->picture1), $image);
                }
            } catch (Exception $e) {

                Log::error('Error in Image Sync: ' . $e->getMessage());
            }
        }

        Manufacturer::updateOrCreate(
            ['code' => $manufacture->manufacturer_id],
            [
                'code' => $manufacture->manufacturer_id,
                'name' => $manufacture->manufacturer,
                'image_url' => $manufacture->picture1,
                'notes' => $manufacture->notes1,
            ]
        );
    }

    private function checkAnddelete($manufacturers)
    {
        $existingCodes = DB::connection('fonus_db')->table('tbl_manufacturer')
            ->pluck('manufacturer_id')
            ->toArray();

        foreach ($manufacturers as $manufacturer) {
            try {
                if (isset($manufacturer->code) && !in_array($manufacturer->code, $existingCodes)) {
                    Log::info("Deleting Manufacturer ID: " . $manufacturer->code . ' - ' . $manufacturer->name);
                    Manufacturer::where('code', $manufacturer->code)->delete();
                }
            } catch (Exception $e) {
                Log::error('Error in Deletion Sync: ' . $e->getMessage());
            }
        }
    }
}
