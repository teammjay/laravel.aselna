<?php

namespace App\Jobs;

use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoreSyncJob implements ShouldQueue
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
        $stores = DB::connection('fonus_db')->table('tbl_seller')->get();
        foreach ($stores as $store) {
            $this->createStore($store);
        }

        //check if connection exists or not
        if (DB::connection('easyesale_db')->getDatabaseName()) {
            Log::info('Connection exists');
            $allStores = DB::connection('easyesale_db')->table('store')->get();
            foreach ($allStores as $store) {
                $this->createStoreFromEasyEsale($store);
            }
        }
    }

    private function createStore(mixed $store)
    {
//        if (Store::where(''where))
        Store::create([
            'id' => (string)Str::uuid(),
            'name' => $store->seller,
        ]);
    }

    private function createStoreFromEasyEsale(mixed $store)
    {
        if (Store::where(['name' => $store->name])->exists()) {
            return;
        }
        Store::create([
            'id' => (string)Str::uuid(),
            'name' => $store->name,
            'marketplace_name' => $store->marketplace_name,
            'store_url' => $store->store_url,
        ]);
    }
}
