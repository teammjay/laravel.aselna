<?php

namespace App\Jobs;

use App\Models\Store;
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'UPC']);

        try {
            $upcSequence = DB::connection('easyesale_db')->table('upc_creation')->cursor();
            foreach ($upcSequence as $upc) {
                $this->createUPCSequence($upc);
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

            CriticalErrorService::report('UPC Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createUPCSequence(mixed $upc)
    {
        // store must exist in aselna (FK on upc_generates.store)
        if (!Store::where('id', $upc->store_id)->exists()) {
            return;
        }
        //company_prefix, product number and number of codes cannot be null
        if (is_null($upc->company_prefix) || is_null($upc->start_product_number) || is_null($upc->number_of_codes)) {
            return;
        }

        // only copy rows missing from aselna, never update or remove existing ones. A prefix + start
        // product number + number of codes is unique (also across stores), the old DB has a few repeats
        $exists = UpcGenerate::where('company_prefix', $upc->company_prefix)
            ->where('product_number', $upc->start_product_number)
            ->where('number_of_codes', $upc->number_of_codes)
            ->exists();
        if ($exists || UpcGenerate::whereKey($upc->id)->exists()) {
            return;
        }

        $upcGenerate = new UpcGenerate([
            'company_prefix' => $upc->company_prefix,
            'number_of_codes' => $upc->number_of_codes,
            'product_number' => $upc->start_product_number,
            'store' => $upc->store_id,
        ]);
        $upcGenerate->id = $upc->id;
        $upcGenerate->created_at = $upc->create_ts ?? now();
        $upcGenerate->save();
    }
}
