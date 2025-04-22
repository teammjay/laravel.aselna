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

    public $timeout = 3000;

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
        $esaleBrands = DB::connection('easyesale_db')->table('cea_brand')->cursor();
        foreach ($esaleBrands as $brand) {
            $this->createSyncBrand($brand);
        }

//        $brands = DB::connection('fonus_db')->table('tbl_brand')->cursor();
//        foreach ($brands as $brand) {
//            $this->createBrand($brand);
//        }
    }

    private function createBrand(mixed $brand)
    {
        Brand::updateOrCreate(
            ['name' => $brand->brand],
            [
                'name' => $brand->brand,
                'notes1' => $brand->notes1,
                'notes2' => $brand->notes2,
                'notes3' => $brand->notes3,
                'image' => $brand->picture1,
            ]
        );
    }

    private function createSyncBrand(mixed $brand)
    {
        Brand::updateOrCreate([
            'id' => $brand->id,
        ], [
            'id' => $brand->id,
            'code' => $brand->code,
            'name' => $brand->name,
            'order_no' => $brand->sort_order,
            'image' => $brand->image_id,
            'thumbnail_image' => $brand->thumbnail_id,
        ]);
    }
}
