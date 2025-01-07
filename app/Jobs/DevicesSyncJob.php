<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceTypes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DevicesSyncJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 3000;

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
        $syncDevices = DB::connection('easyesale_db')->table('cea_device')->cursor();
        foreach ($syncDevices as $device) {
            $this->createSyncDevice($device);
        }

        $devices = DB::connection('fonus_db')->table('tbl_device')->cursor();
        foreach ($devices as $device) {
            $this->createDevice($device);
        }
    }

    private function createDevice(mixed $device)
    {
        // above log prints the following:
        // {"device_id":12,"device_type_id":1,"brand_id":5,"carrier_id":0,"device_model_name":"Curve 8350i","other_name":"","picture1":"12_1.jpg","thumbnail_picture1":"tn_12_1.jpg","status_id":2,"phone_rating":7,"release_date":null,"list":0,"width":"2.40","depth":"0.70","height":"4.40","screen_length":null,"case_size":"(LCASE-08) \/ (Close=>LCASE-19) \/ (Nite-Ize-Wide)","notes1":"","notes2":"","notes3":"","order_no":1000,"cscart":1,"date_added":"2016-10-24 19:42:07","date_updated":"2019-09-07 13:46:12"}
        $deviceType = DeviceTypes::where('code', $device->device_type_id)->first();
//        $deviceSubType = DeviceSubTypes::where('code', $device->device_sub_type_id)->first();
        $brand = Brand::where('code', $device->brand_id)->first();

        if (env('APP_ENV') == 'local') {
            if (!file_exists(storage_path('app/public/device_images/' . $device->picture1))) {
                $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $device->picture1);
                file_put_contents(storage_path('app/public/device_images/' . $device->picture1), $image);
            }

            if (!file_exists(storage_path('app/public/device_images/' . $device->thumbnail_picture1))) {
                $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $device->thumbnail_picture1);
                file_put_contents(storage_path('app/public/device_images/' . $device->thumbnail_picture1), $image);
            }
        }


        Device::updateOrCreate(
            ['code' => $device->device_id],
            [
//                'name' => $device->device_model_name,
//                'code' => $device->device_id,
//                'release_date' => $device->release_date,
                'image' => $device->picture1,
                'thumbnail_image' => $device->thumbnail_picture1,
//                'dimensions_width' => $device->width,
//                'dimensions_length' => $device->depth,
//                'dimensions_height' => $device->height,
//                'notes' => $device->notes1,
//                'order' => $device->order_no,
//                'created_by' => 'system',
//                'updated_by' => 'system',
//                'created_at' => now(),
//                'updated_at' => now(),
//                'status' => $device->status_id == 1 ? 'active' : 'inactive',
//                'device_type_id' => $deviceType->id,
//                'brand_id' => $brand->id,
            ]
        );
    }

    private function createSyncDevice(mixed $device)
    {
//        $rating = DB::connection('easyesale_db')->table('cea_device_rating')->where('id', $device->rating_id)->select('value')->first();
        Device::updateOrCreate([
            'id' => $device->id,
        ], [
            'id' => $device->id,
            'name' => $device->model_name,
            'code' => $device->code,
            'release_date' => $device->release_date,
            'brand_id' => $device->brand_id,
//            'device_series_id' => $device->device_series_id,
            'device_sub_type_id' => $device->device_sub_type_id,
            'device_type_id' => $device->device_type_id,
            'dimensions_height' => $device->dimensions_height,
            'dimensions_length' => $device->dimensions_length,
            'dimensions_length_unit' => $device->dimensions_length_unit,
            'dimensions_width' => $device->dimensions_width,
//            'image' => $device->image_id,
            'notes' => $device->notes,
            'order' => $device->sort_order,
//            'rating_id' => $rating->value,
//            'screen_id' => $device->screen_id,
//            'status' => $device->status,
//            'thumbnail_image' => $device->thumbnail_id
        ]);
    }
}
