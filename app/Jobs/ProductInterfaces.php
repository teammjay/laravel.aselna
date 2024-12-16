<?php

namespace App\Jobs;

use App\Models\Catalog;
use App\Models\Device;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductInterfaces implements ShouldQueue
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
        $productAttributes = DB::connection('easyesale_db')->table('product_cea_interface_link')->get();
        foreach ($productAttributes as $productAttribute) {
            $this->createProductInterfaces($productAttribute);
        }
    }

    private function createProductInterfaces(mixed $productAttribute)
    {
        if (ProductInterface::where(['product_id' => $productAttribute->product_id, 'interface_id' => $productAttribute->cea_interface_id])->exists()) {
            return;
        }
        if (!Catalog::where('product_id', $productAttribute->product_id)->exists()) {
            return;
        }
        ProductInterface::create([
            'id' => (string)Str::uuid(),
            'product_id' => $productAttribute->product_id,
            'interface_id' => $productAttribute->cea_interface_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
