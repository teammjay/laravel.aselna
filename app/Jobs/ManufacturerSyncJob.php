<?php

namespace App\Jobs;

use App\Models\Manufacturer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ManufacturerSyncJob implements ShouldQueue
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
        $manufaturer = DB::connection('fonus_db')->table('tbl_manufacturer')->get();

        foreach ($manufaturer as $manufacture) {
            if (Manufacturer::where(['code' => $manufacture->manufacturer_id])->exists()) {
                continue;
            }
            $this->createManufacturer($manufacture);
        }
    }

    private function createManufacturer(mixed $manufacture)
    {
        Manufacturer::updateOrInsert(
            ['code' => $manufacture->manufacturer_id],
            [
                'id' => (string) Str::uuid(),
                'name' => $manufacture->manufacturer,
                'code' => $manufacture->manufacturer_id,
                'notes' => $manufacture->notes1,
                'client_id' => '',
                'sort_order' => 0,
                'version' => 0,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
