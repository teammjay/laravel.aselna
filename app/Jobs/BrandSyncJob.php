<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\SysFile;
use App\Utils\FileUtility;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BrandSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

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
        $brandData = [
            'id' => $brand->id,
            'code' => $brand->code,
            'name' => $brand->name,
            'order_no' => $brand->sort_order,
            'image' => $brand->image_id,
            'thumbnail_image' => $brand->thumbnail_id,
        ];

        $Image = DB::connection('easyesale_db_wt_prefix')->table('sys_file')->where('id', $brand->image_id)->first();
        try {
            $image_name = $Image->name;
            if (isset($image_name) && env('APP_ENV') == 'local') {
                if (!file_exists(storage_path('app/public/device_images/' . $image_name))) {
                    $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/brand_images/' . $image_name);
                    file_put_contents(storage_path('app/public/brand_images/' . $image_name), $image);
                    $brandData['image'] = $image_name;
                }
            }
        } catch (Exception $e) {
            Log::error('Error in Image Sync: ' . $e->getMessage());
        }

        $Thumbnail = DB::connection('easyesale_db_wt_prefix')->table('sys_file')->where('id', $brand->image_id)->first();
        try {
            $thumbnail_name = $Thumbnail->name;
            if (isset($thumbnail_name) && env('APP_ENV') == 'local') {
                if (!file_exists(storage_path('app/public/device_images/' . $thumbnail_name))) {
                    $thumbnail = file_get_contents('https://fonusaccessory.com/inv/inv_images/brand_images/' . $thumbnail_name);
                    file_put_contents(storage_path('app/public/brand_images/' . $thumbnail_name), $thumbnail);
                    $brandData['thumbnail_image'] = $thumbnail_name;
                }
            }
        } catch (Exception $e) {
            Log::error('Error in Image Sync: ' . $e->getMessage());
        }

        Brand::updateOrCreate([
            'id' => $brand->id,
        ],$brandData );
    }
}
