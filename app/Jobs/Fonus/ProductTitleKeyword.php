<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Catalog;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductTitleKeyword implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 50000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Mark as processing when job starts
        $this->updateTrackingRecord([
            'status' => 'processing',
            'started_at' => now(),
            'tags' => 'Fonus Title Keyword Sync job'
        ]);

        try {
            DB::connection('fonus_db')->table('tbl_title_keywords')
                ->orderBy('title_id')->chunk(1000, function ($titleKeywords) {
                    foreach ($titleKeywords as $titleKeyword) {
                        $this->createOrUpdateTitleKeyword($titleKeyword);
                    }
                });

            \App\Models\ProductTitleKeyword::orderBy('id')
                ->chunk(1000, function ($titleKeywords) {
                    $this->deleteMissingTitleKeywords($titleKeywords);
                });

            // Mark as completed when job finishes successfully
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            // Mark as failed when job fails
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Fonus Title Keyword Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            throw $e; // Re-throw to trigger chain failure handling
        }
    }

    private function createOrUpdateTitleKeyword(mixed $titleKeyword)
    {
        $aselnaProduct = Catalog::select('product_id')->where('product_code', $titleKeyword->product_id)->first();
        if ($aselnaProduct) {
            \App\Models\ProductTitleKeyword::updateOrCreate(
                [
                    'product_id' => $aselnaProduct->product_id,
                    'priority' => $titleKeyword->order_id,
                ],
                [
                    'product_id' => $aselnaProduct->product_id,
                    'priority' => $titleKeyword->order_id,
                    'keyword' => $titleKeyword->title_keyword,
                ]
            );
        }
    }

    private function deleteMissingTitleKeywords($titleKeywords)
    {
        $productKeys = [];
        $keywordsMap = [];

        foreach ($titleKeywords as $titleKeyword) {
            $productId = $titleKeyword->product->product_code;
            $orderId = $titleKeyword->priority;
            $key = $productId . '_' . $orderId;
            $productKeys[] = $key;
            $keywordsMap[$key] = $titleKeyword->id;
        }

        $existingRecords = DB::connection('fonus_db')->table('tbl_title_keywords')
            ->select(DB::raw("CONCAT(product_id, '_', order_id) as `key`"))
            ->whereIn(DB::raw("CONCAT(product_id, '_', order_id)"), array_unique($productKeys))
            ->get();

        $existingSet = [];
        foreach ($existingRecords as $record) {
            $existingSet[$record->key] = true;
        }

        $idsToDelete = [];
        foreach ($keywordsMap as $key => $id) {
            if (!isset($existingSet[$key])) {
                $idsToDelete[] = $id;
            }
        }
        Log::info('Title Keywords to delete :: ' . json_encode($idsToDelete));
        if (!empty($idsToDelete)) {
            \App\Models\ProductTitleKeyword::whereIn('id', $idsToDelete)->delete();
        }
    }
}
