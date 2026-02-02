<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BrandSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public function __construct()
    {
    }

    public function handle(): void
    {
        try {
            $this->updateTrackingRecord(['tags' => 'Brand']);

            $brands = DB::connection('fonus_db')->table('tbl_brand')->get();
            foreach ($brands as $brand) {
                $this->createBrand($brand);
            }

        } catch (\Throwable $e) {

            CriticalErrorService::report('Brand Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createBrand(mixed $brand)
    {
        Log::info('Syncing Brand: ' . $brand->brand_id . ' - ' . $brand->brand);
        $brandData = [
            'code' => $brand->brand_id,
            'name' => $brand->brand,
            'notes1' => $brand->notes1,
            'notes2' => $brand->notes2,
            'notes3' => $brand->notes3,
            'order_no' => $brand->order_no,
            'image' => $brand->picture1,
        ];

        try {
            $image_name = $brand->picture1;
            if (isset($image_name) && env('APP_ENV') != 'local') {
                if (!file_exists(storage_path('app/public/device_images/' . $image_name))) {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/brand_images/' . $image_name);
                    file_put_contents(storage_path('app/public/brand_images/' . $image_name), $image);
                    $brandData['image'] = $image_name;
                }
            }
        } catch (Exception $e) {

            Log::error('Error in Image Sync: ' . $e->getMessage());
        }

        Brand::updateOrCreate([
            'code' => $brand->brand_id
        ], $brandData);
    }
}
