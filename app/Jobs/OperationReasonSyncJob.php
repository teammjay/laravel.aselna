<?php

namespace App\Jobs;

use App\Models\OrderOperationReason;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class OperationReasonSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Operation Reason']);

        try {
            DB::connection('easyesale_db')->table('order_operation_reason')->orderBy('id')
                ->chunk(1000, function ($operationReasons) {
                    foreach ($operationReasons as $operationReason) {
                        $this->createSyncOperationReason($operationReason);
                    }
                });

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Operation Reason Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createSyncOperationReason(mixed $operationReason)
    {
        OrderOperationReason::updateOrCreate(
            ['id' => $operationReason->id],
            [
                'id' => $operationReason->id,
                'store_type_id' => $operationReason->channel_id,
                'operation_type' => $operationReason->operation_type,
                'name' => $operationReason->name,
                'channel_code' => $operationReason->channel_code,
            ]
        );
    }
}
