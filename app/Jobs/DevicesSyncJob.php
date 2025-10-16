<?php

namespace App\Jobs;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceInterface;
use App\Models\DeviceTypes;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use function Adminer\where;

class DevicesSyncJob implements ShouldQueue
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
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Devices Sync Job Start');
        DB::connection('easyesale_db')->table('cea_device')
            ->orderBy('id')
            ->chunk(1000, function ($syncDevices) {
                foreach ($syncDevices as $device) {
                    $this->createSyncDevice($device);
                }
            });
        Log::info('Devices Sync Job End');

        Log::info('Devices Delete Job Start');
        Device::orderBy('id')->chunk(1000, function ($devices) {
            Log::info('Processing chunk of ' . count($devices) . ' devices for deletion check.');
            foreach ($devices as $device) {
                $this->checkAndDelete($device);
            }
        });
        Log::info('Devices Delete Job End');

//        DB::connection('fonus_db')->table('tbl_device')
//            ->orderBy('device_id')
//            ->chunk(1000, function ($devices) {
//                foreach ($devices as $device) {
//                    $this->createDevice($device);
//                }
//            });
    }

    private function createDevice(mixed $device)
    {
        // above log prints the following:
        // {"device_id":12,"device_type_id":1,"brand_id":5,"carrier_id":0,"device_model_name":"Curve 8350i","other_name":"","picture1":"12_1.jpg","thumbnail_picture1":"tn_12_1.jpg","status_id":2,"phone_rating":7,"release_date":null,"list":0,"width":"2.40","depth":"0.70","height":"4.40","screen_length":null,"case_size":"(LCASE-08) \/ (Close=>LCASE-19) \/ (Nite-Ize-Wide)","notes1":"","notes2":"","notes3":"","order_no":1000,"cscart":1,"date_added":"2016-10-24 19:42:07","date_updated":"2019-09-07 13:46:12"}
//        $deviceType = DeviceTypes::where('code', $device->device_type_id)->first();
//        $deviceSubType = DeviceSubTypes::where('code', $device->device_sub_type_id)->first();
//        $brand = Brand::where('code', $device->brand_id)->first();

        if (env('APP_ENV') !== 'local') {
            Log::info('Syncing Images Also');
            try {
                if (!file_exists(storage_path('app/public/device_images/' . $device->picture1))) {
                    $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $device->picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->picture1), $image);
                }
            } catch (Exception $e) {
                Log::error('Error in Image Sync: ' . $e->getMessage());
            }

            if (!file_exists(storage_path('app/public/device_images/' . $device->thumbnail_picture1))) {
                try {
                    $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $device->thumbnail_picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->thumbnail_picture1), $image);
                } catch (Exception $e) {
                    Log::error('Error in Thumbnail Sync: ' . $e->getMessage());
                }
            }
        }


        Device::updateOrCreate(
            ['code' => $device->device_id],
            [
                'name' => $device->device_model_name,
                'code' => $device->device_id,
                'release_date' => $device->release_date,
                'image' => $device->picture1,
                'thumbnail_image' => $device->thumbnail_picture1,
                'dimensions_width' => $device->width,
                'dimensions_length' => $device->depth,
                'dimensions_height' => $device->height,
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
        $createdDevice = Device::where('id', $device->id)->first();

        if (isset($createdDevice) && $createdDevice->updated_at >= $device->update_ts) {
            return;
        }
        Log::info('Syncing Device: ' . $device->model_name . ' (ID: ' . $device->id . ')');

        $deviceData = [
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
            'rating_id' => $device->rating_id,
//            'screen_id' => $device->screen_id,
//            'status' => $device->status,
//            'thumbnail_image' => $device->thumbnail_id
        ];

        try {
            $image_name = strtolower($device->code) . '_1.jpg';
            Log::info('Image URL: ' . 'https://fonusaccessory.com/inv/inv_images/device_images/' . $image_name);
            $image = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $image_name);
            file_put_contents(storage_path('app/public/device_images/' . $image_name), $image);
            $deviceData['image'] = $image_name;
        } catch (Exception $e) {
            Log::error('Error in Image Sync: ' . $e->getMessage());
        }

        try {
            $thumbnail_name = 'th_' . strtolower($device->code) . '_1.jpg';
            $thumbnail = file_get_contents('https://fonusaccessory.com/inv/inv_images/device_images/' . $thumbnail_name);
            file_put_contents(storage_path('app/public/device_images/' . $thumbnail_name), $thumbnail);
            $deviceData['thumbnail_image'] = $thumbnail_name;
        } catch (Exception $e) {
            Log::error('Error in Thumbnail Sync: ' . $e->getMessage());
        }

        Log::info('Data to be synced: ' . json_encode($deviceData));

        Device::updateOrCreate([
            'id' => $device->id,
        ], $deviceData);
    }

    private function checkAndDelete(mixed $device)
    {
        if (DB::connection('easyesale_db')->table('cea_device')->where('id', $device->id)->doesntExist()) {
            DeviceInterface::where('device_id', $device->id)->delete();
            $device->delete();
        }
    }
}
