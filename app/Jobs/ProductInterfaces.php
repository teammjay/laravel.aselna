<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceInterface;
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
        $productAttributes = DB::connection('fonus_db')->table('tbl_product')->get();
        foreach ($productAttributes as $productAttribute) {
            $this->createProductInterfaces($productAttribute);
        }
    }

    private function createProductInterfaces(mixed $productAttribute)
    {
        $productId = Device::where('code', $productAttribute->product_id)->value('id');
        $interfaceId = Interfaces::where('code', $productAttribute->attribute_id)->value('id');
        if (ProductInterface::where(['product_id' => $productId, 'interface_id' => $interfaceId])->exists()) {
            return;
        }
        ProductInterface::create([
            'id' => (string) Str::uuid(),
            'product_id' => $productId,
            'interface_id' => $interfaceId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
