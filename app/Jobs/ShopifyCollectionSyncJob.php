<?php

namespace App\Jobs;

use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ShopifyCollectionSyncJob implements ShouldQueue
{
    use Queueable;

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
                    ShopifyCollectionDeviceSyncJob::dispatch($store->id);
                }
            }

        } catch (\Exception $e) {
            // Log the error or handle it as needed
            Log::error('Error in ShopifyCollectionSyncJob: ' . $e->getMessage());
        }
    }
}
