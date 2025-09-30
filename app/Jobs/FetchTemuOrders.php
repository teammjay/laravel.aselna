<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\Marketplace\Service\TemuService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchTemuOrders implements ShouldQueue
{
    use Queueable;

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
        /* get temu stores*/
        $stores = Store::select('store.*')->join('store_type', 'store_type.id', '=', 'store.store_type_id')->where('store_type.code','temu')->get();

        if(count($stores ?? []) > 0) {
            foreach ($stores as $store) {

                if (env('APP_ENV') === 'local' && $store->name != 'phonil') {
                    continue;
                }
                Log::info('Store : ' . $store->name);

                try {
                    $temuService = new TemuService();
                    $orders = $temuService->getOrders($store);
                } catch (\Exception $e) {
                    Log::info('Error : ' . $e->getMessage());
                }
            }
        }
    }
}
