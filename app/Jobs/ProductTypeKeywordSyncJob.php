<?php

namespace App\Jobs;

use App\Models\ProductTypeKeyword;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductTypeKeywordSyncJob implements ShouldQueue
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

        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Product Type Keywords']);

        try {
            $productTypeKeywordQ = DB::connection('easyesale_db')->table('product_type_keyword')
                ->orderBy('id');

            $productTypeKeywordQ->chunk(1000, function ($productKeywords) {
                foreach ($productKeywords as $productKeyword) {
                    $this->createProductKeyword($productKeyword);
                }
            });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Type Keywords Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductKeyword(mixed $productKeyword)
    {
        ProductTypeKeyword::updateOrCreate([
            'id' => $productKeyword->id
        ], [
            'id' => $productKeyword->id,
            'product_type_id' => $productKeyword->product_type_id,
            'keyword' => $productKeyword->keyword,
            'priority' => $productKeyword->priority,
            'code' => $productKeyword->code,
        ]);
    }
}
