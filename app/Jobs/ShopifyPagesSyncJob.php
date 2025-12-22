<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class ShopifyPagesSyncJob implements ShouldQueue
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
        try {

            $this->updateTrackingRecord(['tags' => 'Shopify Pages']);

            $stores = Store::select('store.*')->with('fileSystem', 'storeType')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'shopify')->get();

            if (count($stores ?? []) > 0) {
                foreach ($stores as $store) {
                    if (env('APP_ENV') == 'local' && trim(strtolower($store->name)) != strtolower('GetAccessory')) {
                        continue;
                    }
                    if (($store->auto_generate_listings ?? 0) == 0) {
                        Log::info('Auto Generate Listings is disabled for store: ' . $store->name);
                        continue;
                    }

                    Log::info('Processing store: ' . $store->name);
                    ShopifyIndvPagesSyncJob::dispatch($store->id);
                }
            }

        } catch (\Throwable $e) {

            \App\Helpers\ErrorLogger::log($e, __METHOD__);

            CriticalErrorService::report('Shopify Pages Job Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            // Log the error or handle it as needed
            Log::error('Error in ShopifyCollectionSyncJob: ' . $e->getMessage());
        }
    }
}
