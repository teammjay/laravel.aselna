<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Models\ProductChannelDetail;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProductChannelDetailSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Product Channel Detail']);

        try {
            DB::connection('easyesale_db')
                ->table('product_channel_detail')
                ->orderBy('id')
                ->chunk(1000, function ($productChannelDetails) {
                    foreach ($productChannelDetails as $productChannelDetail) {
                        $this->createProductChannelDetail($productChannelDetail);
                    }
                });

        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Channel Detail Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductChannelDetail(mixed $productChannelDetail)
    {
        if (Catalog::where('product_id', $productChannelDetail->product_id)->doesntExist()) {
            return;
        }
        ProductChannelDetail::updateOrCreate([
            'store_type_id' => $productChannelDetail->channel_id,
            'product_id' => $productChannelDetail->product_id,
        ], [
            'id' => $productChannelDetail->id,
            'store_type_id' => $productChannelDetail->channel_id,
            'product_id' => $productChannelDetail->product_id,
            'status' => $productChannelDetail->product_status,
            'created_by' => $productChannelDetail->created_by,
            'updated_by' => $productChannelDetail->updated_by,
        ]);
    }
}
