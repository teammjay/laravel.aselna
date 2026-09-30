<?php

namespace App\Console\Commands;

use App\Jobs\Sync\OyderaMasterSwitch;
use App\Models\Store;
use Illuminate\Console\Command;

class OyderaMasterSwitchCommand extends Command
{
    protected $signature = 'oydera:master-switch
                            {store?* : One or more store names or store ids. Omit to sync every store in OyderaMasterSwitch::STORE_NAMES}';

    protected $description = 'Sync store products, listing items, listing item details, listing item images and navigation pages from oydera, then fetch the products already on Shopify, for the Shopify stores.';

    public function handle()
    {
        $stores = $this->argument('store');

        if (empty($stores)) {
            OyderaMasterSwitch::dispatch();
            $this->info('Queued OyderaMasterSwitch for: ' . implode(', ', OyderaMasterSwitch::STORE_NAMES));

            return self::SUCCESS;
        }

        $storeRecords = [];

        foreach ($stores as $store) {
            $storeRecord = Store::select('store.*')
                ->join('store_type', 'store_type.id', '=', 'store.store_type_id')
                ->where('store_type.code', 'shopify')
                ->where(function ($query) use ($store) {
                    $query->where('store.id', $store)
                        ->orWhere('store.name', $store);
                })
                ->first();

            if (!$storeRecord) {
                $this->error('No Shopify store found matching: ' . $store);

                return self::FAILURE;
            }

            $storeRecords[$storeRecord->id] = $storeRecord;
        }

        OyderaMasterSwitch::dispatch(array_keys($storeRecords));
        $this->info('Queued OyderaMasterSwitch for: ' . collect($storeRecords)->map(fn ($store) => $store->name . ' (' . $store->id . ')')->implode(', '));

        return self::SUCCESS;
    }
}
