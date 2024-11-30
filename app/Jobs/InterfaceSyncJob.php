<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\Interfaces;
use App\Models\ProductInterface;
use App\Models\ProductType;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InterfaceSyncJob implements ShouldQueue
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
        $attributes = DB::connection('fonus_db')->table('tbl_attributes')->get();
        foreach ($attributes as $attribute) {
            if (Interfaces::where(['code' => $attribute->attribute_id])->exists()) {
                continue;
            }
            $this->createInterfaces($attribute);
        }

    }

    private function createInterfaces(mixed $attribute)
    {
        $productType = ProductType::where('code', $attribute->category_id)->first();

        Interfaces::updateOrInsert(
            ['code' => $attribute->attribute_id],
            [
                'id' => (string) Str::uuid(),
                'name' => $attribute->attribute,
                'code' => $attribute->attribute_id,
                'product_type_id' => $productType->id,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }




}
