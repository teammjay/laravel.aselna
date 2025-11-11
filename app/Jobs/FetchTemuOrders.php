<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Store;
use App\Services\Marketplace\Service\TemuService;
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
        $this->updateTrackingRecord(['tags' => 'Temu Orders']);
        /* get temu stores*/
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','temu')->where('store.store_status','<>',0)->get();

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
                } catch (\Exception $e) {
                    Log::info('Error : ' . $e->getMessage());
                }
            }

            $this->updateTrackingRecord(['tags' => implode(' ',$tags) .' Temu Orders']);
        }
    }
}
