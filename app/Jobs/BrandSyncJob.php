<?php

namespace App\Jobs;

use App\Models\Brand;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandSyncJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $brands = DB::connection('fonus_db')->table('tbl_brand')->get();
        foreach ($brands as $brand) {
            if (Brand::where(['code' => $brand->brand_id])->exists()) {
                continue;
            }
            $this->createBrand($brand);
        }
    }

    private function createBrand(mixed $brand)
    {
        Brand::updateOrInsert(
            ['code' => $brand->brand_id],
            [
                'id' => (string) Str::uuid(),
                'name' => $brand->brand,
                'code' => $brand->brand_id,
                'notes1' => $brand->notes1,
                'notes2' => $brand->notes2,
                'notes3' => $brand->notes3,
                'order_no' => $brand->order_no,
                'created_by' => 'system',
                'updated_by' => 'system',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
