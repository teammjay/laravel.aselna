<?php

namespace App\Jobs\Shopify;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for SyncShopifyMediaPathsJob: finds Shopify listing images that were pushed
 * (have a reference_id) but never got a path back, and queues a media path sync for each
 * listing item. Meant to be scheduled via scheduled_cron_jobs.
 *
 * Pass a store id to limit the run to one store, e.g. scheduled_cron_jobs.parameters = {"store_id": "<uuid>"}.
 */
class SyncShopifyMissingMediaPathsJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3600;

    public function __construct(
        public ?string $storeId = null
    ) {
    }

    public function handle(): void
    {
        $tags = 'Shopify Missing Media Paths';
        if (isset($this->storeId)) {
            $store = Store::find($this->storeId);
            if (!$store) {
                $this->updateTrackingRecord([
                    'status' => 'failed',
                    'output' => 'Store not found: ' . $this->storeId,
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);
                return;
            }
            $tags = $store->name . ' ' . $tags;
        }

        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => $tags]);

        try {
            $rows = DB::table('listing_item_product_images as lipi')
                ->join('listing_item as li', 'li.id', '=', 'lipi.listing_item_id')
                ->join('store_product as sp', 'sp.id', '=', 'li.store_product_id')
                ->join('store', 'store.id', '=', 'sp.store_id')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->join('manage_marketplace_products as mmp', function ($join) {
                    $join->on('mmp.product_id', '=', 'sp.product_id')
                        ->on('mmp.store_id', '=', 'sp.store_id')
                        ->where('mmp.upload_status', 'success');
                })
                ->where('store_type.code', 'shopify')
                ->where('store.store_status', '<>', 0)
                ->whereNull('lipi.path')
                ->whereNotNull('lipi.reference_id')
                ->whereNotNull('mmp.marketplace_product_id')
                ->when(isset($this->storeId), function ($query) {
                    $query->where('sp.store_id', $this->storeId);
                })
                ->select('lipi.listing_item_id', 'sp.store_id', 'mmp.marketplace_product_id')
                ->distinct()
                ->limit(1000)
                ->get();

            foreach ($rows as $row) {
                SyncShopifyMediaPathsJob::dispatch($row->listing_item_id, $row->store_id, $row->marketplace_product_id);
            }

            Log::info('SyncShopifyMissingMediaPathsJob: queued ' . count($rows) . ' listing items');

            $this->updateTrackingRecord([
                'status' => 'completed',
                'output' => 'Queued ' . count($rows) . ' listing items',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            CriticalErrorService::report('Shopify Missing Media Paths Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine(),
                'method' => __METHOD__,
            ]);
        }
    }
}
