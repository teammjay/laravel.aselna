<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\ProductTags;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use App\Jobs\Concerns\TrackableJob;

class ProductTagSyncJob implements ShouldQueue
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
        $this->updateTrackingRecord(['tags' => 'Product Tags']);

        try {
            $productTypeKeywordQ = DB::connection('easyesale_db')->table('product_tag')
                ->orderBy('id');

            $productTypeKeywordQ->chunk(1000, function ($productTags) {
                foreach ($productTags as $productTag) {
                    $this->createProductTag($productTag);
                }
            });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Product Tags Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createProductTag(mixed $productTag)
    {
        if (Catalog::where('product_id', $productTag->product_id)->doesntExist()) {
            return;
        }
        ProductTags::updateOrCreate([
            'id' => $productTag->id
        ], [
            'id' => $productTag->id,
            'product_id' => $productTag->product_id,
            'name' => $productTag->name,
        ]);
    }
}
