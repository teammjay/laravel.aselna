<?php

namespace App\Jobs;

use App\Models\UpcGenerate;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class UPCSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $this->updateTrackingRecord(['tags' => 'UPC']);

        try {
            $upcSequence = DB::connection('easyesale_db')->table('upc_creation')->cursor();
            foreach ($upcSequence as $upc) {
                $this->createUPCSequence($upc);
            }
        }catch (\Throwable $e) {

            CriticalErrorService::report('UPC Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createUPCSequence(mixed $upc)
    {
        // check is store exists
        if (!DB::connection('easyesale_db')->table('store')->where('id', $upc->store_id)->exists()) {
            return;
        }
        //company_prefix cannot be null
        if (is_null($upc->company_prefix)) {
            return;
        }
        $data = [
            'company_prefix' => $upc->company_prefix,
            'id' => $upc->id,
            'number_of_codes' => $upc->number_of_codes,
            'product_number' => $upc->start_product_number,
            'store' => $upc->store_id,
            'created_at' => now(),
            'updated_at' => now()
        ];
        UpcGenerate::updateOrCreate(['id' => $upc->id],$data );
    }
}
