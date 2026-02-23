<?php

namespace App\Jobs;

use App\Models\ProductSize;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductSizeSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Product Size']);

        try {
            DB::connection('easyesale_db')->table('product_size')->orderBy('id')
                ->chunk(1000, function ($productSizes) {
                    foreach ($productSizes as $productSize) {
                        $this->createProductSize($productSize);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Size Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductSize(mixed $productSize)
    {
        ProductSize::updateOrCreate([
            'id' => $productSize->id,
        ], [
            'id' => $productSize->id,
            'value' => $productSize->value,
            'value_unit' => $productSize->value_unit,
            'short_code' => $productSize->short_code,
            'created_at' => $productSize->create_ts,
            'updated_at' => now(),
        ]);
    }
}
