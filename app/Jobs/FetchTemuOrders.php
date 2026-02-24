<?php

namespace App\Jobs;

use App\Helpers\Common;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Store;
use App\Services\Marketplace\Service\TemuService;
use App\Services\CriticalErrorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchTemuOrders implements ShouldQueue
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
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateTrackingRecord(['status' => 'processing', 'started_at' => now(), 'tags' => 'Temu Orders']);

            /* get active store jobs*/
            $store_ids = Common::getActiveStoreJobs('FetchTemuOrders','temu');
            /* get active store jobs*/

            /* get temu stores*/

            $query = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','temu')->where('store.store_status','<>',0);
            if(!empty($store_ids)){
                $query->whereIn('store.id',$store_ids);
            }
            $stores = $query->get();

            if(count($stores ?? []) > 0) {
                $tags = [];
                foreach ($stores as $store) {

                    if (env('APP_ENV') === 'local' && $store->name != 'phonil') {
                        continue;
                    }
                    Log::info('Store : ' . $store->name);

                    $tags[] = $store->name;

                    try {
                        $temuService = new TemuService();
                        $temuService->getOrders($store);
                    } catch (\Throwable $e) {

                        $extraInfo = [
                            'store_id' => $store->id
                        ];

                        \App\Helpers\ErrorLogger::log($e, __METHOD__, $extraInfo);

                        CriticalErrorService::report('Fetch Temu Orders Job Failed', [
                            'marketplace' => 'Temu',
                            'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                            'method' => __METHOD__
                        ],$extraInfo);

                        Log::info('Error : ' . $e->getMessage());
                    }
                }

                $this->updateTrackingRecord(['tags' => implode(' ',$tags) .' Temu Orders']);
            }
            $this->updateTrackingRecord([
                'status' => 'completed',
                'finished_at' => now()
            ]);
        } catch (\Throwable $e) {
            $this->updateTrackingRecord([
                'status' => 'failed',
                'output' => $e->getMessage(),
                'finished_at' => now()
            ]);
        }
    }
}
