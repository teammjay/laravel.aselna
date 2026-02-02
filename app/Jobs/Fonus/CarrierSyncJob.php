<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Carrier;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CarrierSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 5000;

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
        $this->updateTrackingRecord(['tags' => 'Fonus Carrier Sync job']);

        try {
            $data = DB::connection('fonus_db')->table('tbl_carrier')->get();
            foreach ($data as $carrier) {
                $this->createCarrier($carrier);
            }

            $this->checkAndDeleteMissingCarriers(Carrier::all());
        } catch (\Throwable $e) {
            CriticalErrorService::report('Fonus Carrier Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createCarrier(mixed $carrier)
    {
        if (env('APP_ENV') !== 'local') {
            Log::info('Syncing Images Also');
            try {
                //ensure directory exists
                if (!file_exists(storage_path('app/public/carrier_images/'))) {
                    mkdir(storage_path('app/public/carrier_images/'), 0777, true);
                }
                if (!file_exists(storage_path('app/public/carrier_images/' . $carrier->picture1))) {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/carrier_images/' . $carrier->picture1);
                    file_put_contents(storage_path('app/public/carrier_images/' . $carrier->picture1), $image);
                }
            } catch (Exception $e) {
                Log::error('Error in Image Sync: ' . $e->getMessage());
            }
        }

        Carrier::updateOrCreate(
            ['code' => $carrier->carrier_id],
            [
                'name' => $carrier->carrier,
                'image' => $carrier->picture1,
                'code' => $carrier->carrier_id,
                'sort_order' => $carrier->order_no,
            ]
        );
    }

    private function checkAndDeleteMissingCarriers(\Illuminate\Database\Eloquent\Collection $carrier)
    {
        DB::connection('fonus_db')->table('tbl_carrier')->pluck('carrier_id')->toArray();
        foreach ($carrier as $carriers) {
            $exists = DB::connection('fonus_db')->table('tbl_carrier')
                ->where('carrier_id', $carriers->code)
                ->exists();
            if (!$exists) {
                Carrier::where('code', $carriers->code)->delete();
            }
        }
    }
}
