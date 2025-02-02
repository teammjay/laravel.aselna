<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\StoreProduct;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DataPatch implements ShouldQueue
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
        $products = Catalog::all();
        foreach ($products as $product) {
            Log::info('Product: ' . $product->product_sku);
            StoreProduct::updateOrCreate(
                [
                    'product_id' => $product->product_id,
                    'store_id' => 'c476797e-9682-cc87-c841-5ecc63b9b002',
                ],
                [
                    'product_id' => $product->product_id,
                    'store_id' => 'c476797e-9682-cc87-c841-5ecc63b9b002',
                    'store_type_category_id' => 'ff505a0a-ca50-4c88-be79-2794c21b03cb',
                    'created_by' => 'admin',
                    'updated_by' => 'admin',
                ]
            );
        }
    }
}
