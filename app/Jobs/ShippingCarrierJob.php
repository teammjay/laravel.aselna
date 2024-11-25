<?php

namespace App\Jobs;

use App\Models\ShippingCarrier;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShippingCarrierJob implements ShouldQueue
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
        $carriers = DB::connection('fonus_db')->table('tbl_carrier')->get();
        foreach ($carriers as $carrier) {
            if (ShippingCarrier::where(['code' => $carrier->carrier_id])->exists()) continue;

            $this->createCarrier($carrier);
        }
    }

    private function createCarrier(mixed $carrier)
    {
        ShippingCarrier::updateOrInsert(
            ['code' => $carrier->carrier_id],
            [
                'id' => (string) Str::uuid(),
                'code' => $carrier->carrier_id,
                'name' => $carrier->carrier,
                'created_by' => 'system',
                'updated_by' => 'system',
            ]
        );
    }
}
