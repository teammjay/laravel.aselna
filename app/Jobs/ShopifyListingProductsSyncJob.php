<?php

namespace App\Jobs;

use App\Models\ListingItem;
use App\Models\Store;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShopifyListingProductsSyncJob implements ShouldQueue
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
        $storeIds = Store::join('store_type', 'store_type.id', '=', 'store.store_type_id')
            ->where('store_type.code', 'shopify')
            ->pluck('store.id');
        DB::connection('easyesale_db')->table('listing_item')
            ->whereIn('store_product_id', function ($query) use ($storeIds) {
                $query->select('store_product.id')
                    ->from('store_product')
                    ->whereIn('store_id',$storeIds);
            })
            ->orderBy('id')
            ->chunk(100, function ($items) {
                foreach ($items as $item) {
                    $this->syncListingItem($item);
                }
            });
    }

    private function syncListingItem(mixed $item)
    {
        Log::info('Syncing listing item: ' . $item->id);
        ListingItem::updateOrCreate(
            [
                'store_sku' => $item->store_sku,
            ],
            [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'sku' => $item->sku,
                'store_sku' => $item->store_sku,
                'price' => $item->price,
                'sync_status' => $item->sync_status,
                'quantity' => $item->quantity,
                'store_product_id' => $item->store_product_id,
                'status' => $item->sync_status,
                'store_url' => $item->store_url,
            ]
        );
    }
}
