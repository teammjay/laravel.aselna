<?php

namespace App\Jobs\Sync;

use App\Jobs\Concerns\TrackableJob;
use App\Jobs\Fonus\ChainCompletionJob;
use App\Jobs\ListingItemProductImageSyncJob;
use App\Jobs\Shopify\SyncShopifyItems;
use App\Models\JobStatus;
use App\Models\Store;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OyderaMasterSwitch implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    /**
     * The Shopify stores that are synced from oydera when no single store is given.
     */
    public const STORE_NAMES = ['UZID', 'OdeMobile', 'AccessoryChoice'];

    /**
     * Picked up by the Queue::createPayloadUsing hook in AppServiceProvider and used for the
     * job_statuses tags. Only set for a single-store run.
     */
    public $store_name;

    /**
     * Create a new job instance.
     *
     * @param  string|null  $storeId  Sync only this store; null syncs every store in STORE_NAMES.
     */
    public function __construct(
        public ?string $storeId = null
    ) {
        $this->chainId = (string) Str::uuid();

        if (!empty($storeId)) {
            $this->store_name = Store::where('id', $storeId)->value('name') ?? $storeId;
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $tags = trim(($this->store_name ?? '') . ' Oydera Master Switch');

        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => $tags]);

        try {
            $storesQ = Store::select('store.*')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify');

            if (!empty($this->storeId)) {
                $storesQ->where('store.id', $this->storeId);
            } else {
                $storesQ->whereIn('store.name', self::STORE_NAMES);
            }

            $stores = $storesQ->get();

            // Keep the stores in the order they are listed in STORE_NAMES
            if (empty($this->storeId)) {
                $stores = $stores->sortBy(fn ($store) => array_search($store->name, self::STORE_NAMES))->values();
            }

            if (count($stores ?? []) == 0) {
                Log::info('OyderaMasterSwitch: no matching Shopify store found.');
                $this->updateTrackingRecord([
                    'status' => 'completed',
                    'output' => 'No matching Shopify store found.',
                    'finished_at' => now()
                ]);
                return;
            }

            $chain = [];

            foreach ($stores as $store) {
                Log::info('OyderaMasterSwitch: queueing sync for store ' . $store->name . ' (' . $store->id . ')');

                // 1. store products
                $chain[] = new SyncShopifyItems($store->id, SyncShopifyItems::TASK_STORE_PRODUCT);

                // 2. listing items
                $chain[] = new SyncShopifyItems($store->id, SyncShopifyItems::TASK_LISTING_ITEM);

                // 3. listing item details
                $chain[] = new SyncShopifyItems($store->id, SyncShopifyItems::TASK_LISTING_ITEM_DETAIL);

                // 4. listing item images
                $chain[] = new ListingItemProductImageSyncJob($store->id);
            }

            $chain[] = new ChainCompletionJob($this->jobId);

            // Create pending record for the completion job
            $this->createPendingRecordsForChain($chain, $this->jobId);

            // Store parent job ID for the catch callback
            $parentJobId = $this->jobId;

            Bus::chain($chain)
                ->catch(function (\Throwable $e) use ($parentJobId) {
                    // Mark parent as failed if chain fails
                    JobStatus::where('job_id', $parentJobId)
                        ->update(['status' => 'failed', 'output' => $e->getMessage(), 'finished_at' => now()]);
                })
                ->dispatch();
        } catch (\Throwable $e) {

            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);

            CriticalErrorService::report('Oydera Master Switch Failed', [
                'marketplace' => 'Shopify',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());
        }
    }
}
