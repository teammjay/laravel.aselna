<?php

namespace App\Console\Commands;

use App\Jobs\Sync\OyderaMasterSwitch;
use App\Models\Store;
use Illuminate\Console\Command;

class OyderaMasterSwitchCommand extends Command
{
    protected $signature = 'oydera:master-switch
                            {store? : Store name or store id. Omit to sync every store in OyderaMasterSwitch::STORE_NAMES}';

    protected $description = 'Sync store products, listing items, listing item details, listing item images and navigation pages from oydera, then fetch the products already on Shopify, for the Shopify stores.';

    public function handle()
    {
        $store = $this->argument('store');

        if (empty($store)) {
            OyderaMasterSwitch::dispatch();
            $this->info('Queued OyderaMasterSwitch for: ' . implode(', ', OyderaMasterSwitch::STORE_NAMES));

            return self::SUCCESS;
        }

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

        OyderaMasterSwitch::dispatch($storeRecord->id);
        $this->info('Queued OyderaMasterSwitch for: ' . $storeRecord->name . ' (' . $storeRecord->id . ')');

        return self::SUCCESS;
    }
}
