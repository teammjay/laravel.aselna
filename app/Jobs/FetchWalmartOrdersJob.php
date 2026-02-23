<?php

namespace App\Jobs;

use App\Constants\OrderStatus;
use App\Helpers\Common;
use App\Models\Customer;
use App\Models\ListingItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderOperation;
use App\Models\OrderOperationItem;
use App\Models\OrderSynchronization;
use App\Models\Store;
use App\Services\CriticalErrorService;
use App\Services\Marketplace\Service\WalmartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Jobs\Concerns\TrackableJob;

class FetchWalmartOrdersJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 3000;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Testing");
        $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Walmart Orders']);

        try {
            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('FetchWalmartOrdersJob', 'walmart');
            /* get active store jobs*/

            $query = Store::select('store.*', 'store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'walmart')->where('store.store_status', '<>', 0);
            if (!empty($store_ids)) {
                $query->whereIn('store.id', $store_ids);
            }
            $stores = $query->get();

            //$stores = Store::select('store.*','store_type.name as storeTypeName')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code', 'walmart')->where('store.store_status','<>',0)->whereIn('store.id',$store_ids)->get();

            if (count($stores ?? []) > 0) {
                $tags = [];
                foreach ($stores as $store) {

                    if (env('APP_ENV') === 'local' && $store->name != 'Accessorychoice') {
                        continue;
                    }

                    $tags[] = $store->name;

                    $walmartService = new WalmartService();
                    $walmartService->getOrders($store);

                }

                $this->updateTrackingRecord(['tags' => implode(' ', $tags) . ' Walmart Orders']);
            }

            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        }catch (\Throwable $e) {

            CriticalErrorService::report('Fetch Walmart Orders Job Failed', [
                'marketplace' => 'Walmart',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);

            Log::error('Error: ' . $e->getMessage());

        }
    }
}
