<?php

namespace App\Jobs\Magento;

use App\Constants\WebStorePageType;
use App\Jobs\Concerns\TrackableJob;
use App\Models\Device;
use App\Models\Store;
use App\Models\StoreNavigationPage;
use App\Services\Auth\MagentoAuthService;
use App\Services\CriticalErrorService;
use App\Utils\FileUtility;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MagentoDeviceSyncJob implements ShouldQueue
{
    use Queueable, TrackableJob {
        TrackableJob::withChain insteadof Queueable;
        Queueable::withChain as queueableWithChain;
    }

    public $timeout = 6000;

    protected $storeId;

    public function __construct($storeId)
    {
        $this->storeId = $storeId;
    }

    public function handle(): void
    {
        $this->updateTrackingRecord(['tags' => 'Magento Device Sync']);

        try {
            $store = Store::findOrFail($this->storeId);
            $magentoAuthService = new MagentoAuthService($store->seller_id, $store->secret_key, $store->service_url);

            $devices = Device::where('status', 'active')
                ->get();
            foreach ($devices as $device) {
                $storePage = StoreNavigationPage::where('store_id', $this->storeId)
                    ->where('page_type', WebStorePageType::DEVICE)
                    ->where('device_id', $device->id)
                    ->first();

                Log::info("Syncing Device: " . $device->name);
                $brand = StoreNavigationPage::where('store_id', $this->storeId)
                    ->where('page_type', WebStorePageType::BRAND)
                    ->where('device_brand_id', $device->brand_id)
                    ->first();
                if (!isset($brand->page_id)) continue;

                $handle = $this->generateHandle($device->name);

                $imagePath = '';
                if ($device->image) {
                    $ext = pathinfo($device->image, PATHINFO_EXTENSION) ?: 'jpg';
                    $remotePath = '/store-images/' . $this->storeId . '/devices/' . $handle . '.' . $ext;
                    try {
                        $imagePath = FileUtility::uploadImage($store, 'app/public/device_images/' . $device->image, $remotePath);
                    } catch (\Exception $e) {
                        Log::error("Image upload failed for device {$device->name}: " . $e->getMessage());
                    }
                }

                $thumbnailPath = '';
                if ($device->thumbnail_image) {
                    $ext = pathinfo($device->thumbnail_image, PATHINFO_EXTENSION) ?: 'jpg';
                    $remotePath = '/store-images/' . $this->storeId . '/devices/thumb_' . $handle . '.' . $ext;
                    try {
                        $thumbnailPath = FileUtility::uploadImage($store, 'app/public/device_images/' . $device->thumbnail_image, $remotePath);
                    } catch (\Exception $e) {
                        Log::error("Thumbnail upload failed for device {$device->name}: " . $e->getMessage());
                    }
                }

                $deviceData = [
                    'name' => $device->name,
                    'brand_id' => $brand->page_id ?? null,
                    'url_key' => $handle,
                    'image' => $imagePath,
                    'thumbnail_image' => $thumbnailPath,
                    'dimensions_height' => $device->dimensions_height,
                    'dimensions_width' => $device->dimensions_width,
                    'dimensions_length' => $device->dimensions_length,
                    'dimensions_length_unit' => $device->dimensions_length_unit,
                    'release_date' => $device->getRawOriginal('release_date') ? Carbon::parse($device->getRawOriginal('release_date'))->format('Y-m-d') : null,
                    'notes' => $device->notes,
                    'order' => $device->order,
                    'code' => $device->code,
                    'rating' => isset($device->rating->value) ? (float)$device->rating->value : 0,
                    'status' => $device->status == 'active' ? 1 : 0,
                ];

                Log::info('Magento Device create Data :: ' .  json_encode($deviceData));
                if (isset($storePage) && $storePage->page_id) {
                    Log::info("Device already synced: " . $device->name . " Magento ID: " . $storePage->page_id . ". Updating...");
                    $response = $magentoAuthService->updateDevice($storePage->page_id, $deviceData);
                } else {
                    $response = $magentoAuthService->createDevice($deviceData);
                }

                Log::info('Magento Device Response :: ' .  json_encode($response));

                if (isset($response['id'])) {
                    $magentoDeviceId = $response['id'];

                    StoreNavigationPage::updateOrCreate(
                        [
                            'store_id' => $this->storeId,
                            'page_type' => WebStorePageType::DEVICE,
                            'device_id' => $device->id,
                        ],
                        [
                            'page_id' => $magentoDeviceId,
                            'device_brand_id' => $device->brand_id,
                            'url_path' => $handle,
                            'title' => $device->name,
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            CriticalErrorService::report('Magento Device Sync Job Failed', [
                'marketplace' => 'Magento',
                'reason' => $e->getMessage() . ' in file: ' . $e->getFile() . ' on line: ' . $e->getLine() ?? 'Unknown',
                'method' => __METHOD__
            ]);
        }
    }

    private function generateHandle($title)
    {
        // Convert to lowercase
        $handle = mb_strtolower($title, 'UTF-8');

        // Normalize accented characters (e.g., é → e)
        $handle = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $handle);

        // Replace apostrophes
        $handle = preg_replace("/'/", '', $handle);

        // Replace any non-alphanumeric characters (excluding spaces and dots) with nothing
        $handle = preg_replace("/[^a-z0-9\s.]/", '', $handle);

        // Replace dots with hyphens (e.g., 12.3 → 12-3)
        $handle = str_replace('.', '-', $handle);

        // Replace whitespace with hyphens
        $handle = preg_replace("/[\s]+/", '-', $handle);

        // Remove multiple consecutive hyphens
        $handle = preg_replace("/-+/", '-', $handle);

        // Trim hyphens from beginning and end
        $handle = trim($handle, '-');

        return $handle;
    }
}
