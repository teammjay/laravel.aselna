<?php

namespace App\Jobs;

use App\Models\ProductCondition;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductConditionJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Product Conditions']);

        try {
            $productCondition = DB::connection('easyesale_db')->table('product_condition')->cursor();
            foreach ($productCondition as $condition) {
                $this->createProductCondition($condition);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Condition Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductCondition(mixed $condition)
    {
        ProductCondition::updateOrCreate(
            ['id' => $condition->id],
            [
                'id' => $condition->id,
                'name' => $condition->name,
                'code' => $condition->code,
            ]
        );
    }
}
