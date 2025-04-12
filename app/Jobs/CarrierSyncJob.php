<?php

namespace App\Jobs;

use App\Models\Carrier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class CarrierSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $carriers = DB::connection('easyesale_db')->table('cea_carrier')->cursor();

        foreach ($carriers as $carrier) {
            $this->createCarrier($carrier);
        }
    }

    private function createCarrier($carrier)
    {
        $carrierData = [
            'id' => $carrier->id,
            'name' => $carrier->name,
            'code' => $carrier->code,
            'client' => $carrier->client,
//            'image' => $carrier->image,
//            'thumbnail_image' => $carrier->thumbnail_image,
            'sort_order' => $carrier->sort_order,
            'created_by' => 'system',
            'updated_by' => 'system',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        Carrier::updateOrCreate(['id' => $carrier->id], $carrierData);
    }
}
