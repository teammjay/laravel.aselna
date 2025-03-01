<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\StoreType;
use App\Services\Marketplace\MarketplaceServiceFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MagentoSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:magento-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //get all the store with storeType magento
        $storeType = StoreType::where('name', 'magento')->first();
        $stores = Store::where('store_type_id', $storeType->id)->get();

        foreach ($stores as $store) {
            $this->SyncAttributesIfNeeded($store);
        }
    }

    private function SyncAttributesIfNeeded($store)
    {
        $magentoService = MarketplaceServiceFactory::create('magento');
        Log::info('Syncing attributes for store: ' . $store->name);
        Log::info('Store Keys :: ' . $store->seller_id . '  Secret KEy ' . $store->secret_key);

        $magentoService->syncAttributes($store);
    }
}
