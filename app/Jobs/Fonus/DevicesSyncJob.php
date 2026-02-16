<?php

namespace App\Jobs\Fonus;

use App\Jobs\Concerns\TrackableJob;
use App\Models\Brand;
use App\Models\Device;
use App\Models\DeviceRating;
use App\Models\DeviceSubTypes;
use App\Models\DeviceTypes;
use App\Services\CriticalErrorService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $this->updateTrackingRecord(['tags' => 'Fonus Devices Sync']);

        try {
            DB::connection('fonus_db')->table('tbl_device')
                ->orderBy('device_id')
                ->chunk(1000, function ($devices) {
                    foreach ($devices as $device) {
                        $this->createDevice($device);
                    }
                });

            DB::connection('fonus_db')->table('tbl_gadget')
                ->orderBy('gadget_id')
                ->chunk(1000, function ($devices) {
                    foreach ($devices as $device) {
                        $this->createGadgetDevice($device);
                    }
                });
        }catch (\Throwable $e) {

            CriticalErrorService::report('Fonus Device Sync Job Failed', [
                'marketplace' => 'N/A',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function createDevice(mixed $device)
    {

        $alreadyExists = Device::where('code', $device->device_id)->first();
        if (isset($alreadyExists) && $alreadyExists->updated_at > $device->date_updated) {
            return;
        }
        Log::info('Syncing Device ID: ' . $device->device_id . ' - ' . $device->device_model_name);

        if (env('APP_ENV') !== 'local') {
            Log::info('Syncing Images Also');
            try {
                if (!file_exists(storage_path('app/public/device_images/' . $device->picture1))) {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/device_images/' . $device->picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->picture1), $image);
                }
            } catch (Exception $e) {

                Log::error('Error in Image Sync: ' . $e->getMessage());
            }

            if (!file_exists(storage_path('app/public/device_images/' . $device->thumbnail_picture1))) {
                try {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/device_images/' . $device->thumbnail_picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->thumbnail_picture1), $image);
                } catch (Exception $e) {

                    Log::error('Error in Thumbnail Sync: ' . $e->getMessage());
                }
            }
        }

        $brand = Brand::where('code', $device->brand_id)->first();
        $devicetypes = DeviceTypes::where('code', $device->device_type_id)->first();
        $deviceRating = DeviceRating::where('value', $device->phone_rating)->first();

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
                'order' => $device->order_no,
                'status' => $device->status_id == 1 ? 'active' : 'inactive',
                'brand_id' => $brand ? $brand->id : null,
                'device_type_id' => $devicetypes ? $devicetypes->id : null,
                'rating_id' => $deviceRating ? $deviceRating->id : null,
            ]
        );
    }

    private function createGadgetDevice(mixed $device)
    {

        $alreadyExists = Device::where('code', $device->gadget_id)->first();
        // exlude where date is $device->date_updated 0000-00-00 00:00:00
        if (isset($alreadyExists) && $alreadyExists->updated_at > $device->date_updated && $device->date_updated != '0000-00-00 00:00:00') {
            return;
        }
        Log::info('Syncing Device ID: ' . $device->gadget_id . ' - ' . $device->gadget_model_name);

        if (env('APP_ENV') !== 'local') {
            Log::info('Syncing Images Also');
            try {
                if (!file_exists(storage_path('app/public/device_images/' . $device->picture1))) {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/device_images/' . $device->picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->picture1), $image);
                }
            } catch (Exception $e) {

                Log::error('Error in Image Sync: ' . $e->getMessage());
            }

            if (!file_exists(storage_path('app/public/device_images/' . $device->thumbnail_picture1))) {
                try {
                    $image = file_get_contents(env('FONUS_BASE_URL') . 'inv_images/device_images/' . $device->thumbnail_picture1);
                    file_put_contents(storage_path('app/public/device_images/' . $device->thumbnail_picture1), $image);
                } catch (Exception $e) {

                    Log::error('Error in Thumbnail Sync: ' . $e->getMessage());
                }
            }
        }

        $brand = Brand::where('code', $device->brand_id)->first();
        $devicetypes = DeviceTypes::where('code', $device->gadget_type_id)->first();
        $deviceSubType = DeviceSubTypes::where('code', $device->device_type_sub_id)->first();

        Device::updateOrCreate(
            ['code' => $device->gadget_id],
            [
                'name' => $device->gadget_model_name,
                'code' => $device->gadget_id,
                'release_date' => $device->release_date,
                'image' => $device->picture1,
                'thumbnail_image' => $device->thumbnail_picture1,
                'dimensions_width' => $device->width,
                'dimensions_length' => $device->depth,
                'dimensions_height' => $device->height,
                'notes' => $device->notes1,
                'order' => $device->gadget_id,
                'status' => $device->status_id == 1 ? 'active' : 'inactive',
                'brand_id' => $brand ? $brand->id : null,
                'device_type_id' => $devicetypes ? $devicetypes->id : null,
                'device_sub_type_id' => $deviceSubType ? $deviceSubType->id : null,
            ]
        );
    }
}
